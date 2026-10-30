<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Pasarelas;

/**
 * CU20 · La pasarela de pago es un servicio externo, no un actor. Cpago solo conoce esta interfaz,
 * así la pasarela simulada (defensa) y Stripe (modo prueba) se intercambian sin tocar el caso de uso.
 *
 * Respuesta normalizada de verificar(): ['estado' => aprobado|rechazado|cancelado|pendiente, 'importe' => '150.00', 'moneda' => 'BOB']
 */
interface PasarelaPago
{
    /** Paso 3: registra el cobro en la pasarela y devuelve su referencia. */
    public function iniciar(array $pago, array $cita, string $urlRetorno): string;

    /**
     * Datos para mostrar el formulario de pago DENTRO de la página del sistema (sin salir del sitio):
     * ['tipo' => 'simulada'] o ['tipo' => 'stripe', 'client_secret' => …, 'clave_publica' => …].
     * null si el intento ya no admite pagar (por ejemplo, la sesión de Stripe expiró).
     */
    public function formulario(array $pago): ?array;

    /** Paso 5: consulta a la pasarela cómo terminó el cobro (nunca se confía solo en lo que trae el navegador). */
    public function verificar(array $pago, array $datosRetorno): array;

    /** A2: cierra en la pasarela un intento que no se completó. */
    public function anular(array $pago): void;

    /** A3: devolución total; lanza ExcepcionNegocio si la pasarela no la confirma. */
    public function reembolsar(array $pago): void;
}
