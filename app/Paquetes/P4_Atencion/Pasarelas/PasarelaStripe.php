<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Pasarelas;

use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;

/**
 * Stripe en modo prueba mediante su API REST (cURL, sin SDK). Usa Stripe Checkout EMBEBIDO: el sistema
 * crea una «sesión de pago» y Stripe.js dibuja su formulario dentro de nuestra página de pago (el usuario
 * no sale del sitio). Para saber el resultado se consulta la sesión directamente a Stripe (paso 5),
 * tanto al volver el usuario como al llegar el webhook.
 *
 * Tarjeta de prueba: 4242 4242 4242 4242, cualquier fecha futura y CVC.
 */
final class PasarelaStripe implements PasarelaPago
{
    private const API = 'https://api.stripe.com/v1/';
    private const TOLERANCIA_WEBHOOK = 300;

    public function __construct(private string $claveSecreta, private string $secretoWebhook = '', private string $clavePublica = '')
    {
    }

    public function iniciar(array $pago, array $cita, string $urlRetorno): string
    {
        $sesion = $this->llamar('POST', 'checkout/sessions', [
            'mode'                => 'payment',
            'ui_mode'             => 'embedded_page',
            'client_reference_id' => $pago['id'],
            'metadata'            => ['pago_id' => $pago['id'], 'cita' => $cita['codigo']],
            'line_items'          => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => strtolower($pago['moneda']),
                    'unit_amount'  => (int) round((float) $pago['importe'] * 100),
                    'product_data' => ['name' => 'Cita ' . $cita['codigo'] . ' · ' . $cita['especialidad'] . ' · Clínica Vida Salud'],
                ],
            ]],
            // Al terminar el pago, Stripe lleva al usuario de vuelta a nuestro retorno (paso 4).
            'return_url' => $urlRetorno . '&session_id={CHECKOUT_SESSION_ID}',
        ], (string) $pago['clave_idempotencia']);

        return $sesion['id'];
    }

    /** Mientras la sesión siga abierta, su client_secret permite dibujar el formulario de Stripe en la página. */
    public function formulario(array $pago): ?array
    {
        if (empty($pago['referencia_externa'])) {
            return null;
        }
        if ($this->clavePublica === '') {
            throw new ExcepcionNegocio('Stripe no está configurado (falta STRIPE_PUBLIC_KEY).');
        }
        $sesion = $this->llamar('GET', 'checkout/sessions/' . rawurlencode($pago['referencia_externa']));
        if ($sesion['status'] !== 'open' || empty($sesion['client_secret'])) {
            return null;
        }
        return ['tipo' => 'stripe', 'client_secret' => $sesion['client_secret'], 'clave_publica' => $this->clavePublica];
    }

    public function verificar(array $pago, array $datosRetorno): array
    {
        if (empty($pago['referencia_externa'])) {
            return ['estado' => 'pendiente'];
        }
        $sesion = $this->llamar('GET', 'checkout/sessions/' . rawurlencode($pago['referencia_externa']));
        $estado = match (true) {
            $sesion['payment_status'] === 'paid' => 'aprobado',
            $sesion['status'] === 'expired'      => 'cancelado',
            default                              => 'pendiente',
        };
        return [
            'estado'  => $estado,
            'importe' => number_format(((int) $sesion['amount_total']) / 100, 2, '.', ''),
            'moneda'  => strtoupper((string) $sesion['currency']),
        ];
    }

    public function anular(array $pago): void
    {
        if (empty($pago['referencia_externa'])) {
            return;
        }
        $sesion = $this->llamar('GET', 'checkout/sessions/' . rawurlencode($pago['referencia_externa']));
        if ($sesion['status'] === 'open') {
            $this->llamar('POST', 'checkout/sessions/' . rawurlencode($pago['referencia_externa']) . '/expire');
        }
    }

    public function reembolsar(array $pago): void
    {
        $sesion = $this->llamar('GET', 'checkout/sessions/' . rawurlencode((string) $pago['referencia_externa']));
        if (empty($sesion['payment_intent'])) {
            throw new ExcepcionNegocio('Stripe no tiene un cobro asociado a este pago.');
        }
        $devolucion = $this->llamar('POST', 'refunds', ['payment_intent' => $sesion['payment_intent']], 'devolucion-' . $pago['clave_idempotencia']);
        if (!in_array($devolucion['status'] ?? '', ['succeeded', 'pending'], true)) {
            throw new ExcepcionNegocio('Stripe no confirmó la devolución; intenta nuevamente.');
        }
    }

    /**
     * Webhook: la cabecera Stripe-Signature trae «t=marca,v1=firma». La firma es HMAC-SHA256 de "t.cuerpo"
     * con el secreto whsec_…; si coincide y es reciente, el aviso viene de Stripe. Devuelve el evento.
     */
    public function leerWebhook(string $cuerpo, string $cabecera, ?int $ahora = null): array
    {
        $marca = 0;
        $firmas = [];
        foreach (explode(',', $cabecera) as $parte) {
            [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');
            if ($clave === 't') {
                $marca = (int) $valor;
            } elseif ($clave === 'v1') {
                $firmas[] = $valor;
            }
        }
        $esperada = hash_hmac('sha256', $marca . '.' . $cuerpo, $this->secretoWebhook);
        $valida = $this->secretoWebhook !== '' && array_filter($firmas, static fn (string $f): bool => hash_equals($esperada, $f));
        if (!$valida || abs(($ahora ?? time()) - $marca) > self::TOLERANCIA_WEBHOOK) {
            throw new ExcepcionNegocio('Firma del webhook no válida.');
        }
        return json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
    }

    /** Llamada a la API de Stripe. La clave de idempotencia evita cobrar dos veces si se repite la solicitud (E3). */
    private function llamar(string $metodo, string $ruta, array $datos = [], ?string $idempotencia = null): array
    {
        if ($this->claveSecreta === '') {
            throw new ExcepcionNegocio('Stripe no está configurado (falta STRIPE_SECRET_KEY).');
        }
        $cabeceras = ['Authorization: Bearer ' . $this->claveSecreta];
        if ($idempotencia) {
            $cabeceras[] = 'Idempotency-Key: ' . $idempotencia;
        }
        $curl = curl_init(self::API . $ruta);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $cabeceras,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $cuerpo = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorRed = curl_error($curl);
        curl_close($curl);

        $respuesta = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        if (!is_array($respuesta) || $codigo >= 400) {
            error_log('[Vida Salud] Stripe ' . $metodo . ' ' . $ruta . ' → ' . $codigo . ' ' . ($errorRed ?: ($respuesta['error']['message'] ?? 'respuesta no válida')));
            throw new ExcepcionNegocio('No se pudo comunicar con la pasarela de pago. Intenta nuevamente.');
        }
        return $respuesta;
    }
}
