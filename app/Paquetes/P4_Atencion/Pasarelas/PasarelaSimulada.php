<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Pasarelas;

use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;

/**
 * «Vida Pay»: pasarela simulada para la defensa, sin cuentas ni internet. Imita a una pasarela real:
 * su formulario (embebido en la página de pago) cobra la tarjeta y devuelve el resultado FIRMADO (HMAC-SHA256). El sistema verifica
 * esa firma antes de cambiar el estado, así nadie puede aprobar un pago editando la URL.
 *
 * Tarjetas de prueba (como las de Stripe): 4242 4242 4242 4242 se aprueba, 4000 0000 0000 0002 se rechaza.
 */
final class PasarelaSimulada implements PasarelaPago
{
    public function __construct(private string $clave)
    {
    }

    public function iniciar(array $pago, array $cita, string $urlRetorno): string
    {
        return 'VP-' . strtoupper(bin2hex(random_bytes(5)));
    }

    /** El formulario de tarjeta de Vida Pay se dibuja en la misma página de pago. */
    public function formulario(array $pago): ?array
    {
        return ['tipo' => 'simulada'];
    }

    /** Lo que hace la pasarela al presionar «Pagar»: decide el resultado y lo firma. */
    public function cobrar(array $pago, string $numeroTarjeta): array
    {
        $digitos = preg_replace('/\D/', '', $numeroTarjeta);
        $respuesta = [
            'referencia' => (string) $pago['referencia_externa'],
            'estado'     => strlen($digitos) === 16 && !str_ends_with($digitos, '0002') ? 'aprobado' : 'rechazado',
            'importe'    => number_format((float) $pago['importe'], 2, '.', ''),
            'moneda'     => (string) $pago['moneda'],
        ];
        return $respuesta + ['firma' => $this->firmar($respuesta)];
    }

    public function verificar(array $pago, array $datosRetorno): array
    {
        // A2: sin respuesta firmada (el usuario cerró la ventana), la pasarela no confirmó nada.
        if (empty($datosRetorno['firma'])) {
            return ['estado' => 'pendiente'];
        }
        $respuesta = [
            'referencia' => (string) ($datosRetorno['referencia'] ?? ''),
            'estado'     => (string) ($datosRetorno['estado'] ?? ''),
            'importe'    => (string) ($datosRetorno['importe'] ?? ''),
            'moneda'     => (string) ($datosRetorno['moneda'] ?? ''),
        ];
        $firmaValida = hash_equals($this->firmar($respuesta), (string) $datosRetorno['firma']);
        if (!$firmaValida || $respuesta['referencia'] !== (string) $pago['referencia_externa'] || !in_array($respuesta['estado'], ['aprobado', 'rechazado'], true)) {
            throw new ExcepcionNegocio('La respuesta de la pasarela no es válida; el pago no cambió de estado.');
        }
        return $respuesta;
    }

    public function anular(array $pago): void
    {
        // Vida Pay no guarda nada pendiente de su lado.
    }

    public function reembolsar(array $pago): void
    {
        // Vida Pay siempre confirma la devolución.
    }

    private function firmar(array $respuesta): string
    {
        return hash_hmac('sha256', implode('|', [$respuesta['referencia'], $respuesta['estado'], $respuesta['importe'], $respuesta['moneda']]), $this->clave);
    }
}
