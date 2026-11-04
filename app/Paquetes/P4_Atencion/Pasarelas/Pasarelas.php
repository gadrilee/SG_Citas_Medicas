<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Pasarelas;

use VidaSalud\Nucleo\Configuracion;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;

/** Entrega la implementación que corresponde a un código del catálogo pasarela_pago. */
final class Pasarelas
{
    public static function crear(string $codigo): PasarelaPago
    {
        return match ($codigo) {
            'simulada' => self::simulada(),
            'stripe'   => self::stripe(),
            default    => throw new ExcepcionNegocio('La pasarela de pago no está disponible.'),
        };
    }

    public static function simulada(): PasarelaSimulada
    {
        return new PasarelaSimulada((string) Configuracion::obtener('clave_app'));
    }

    public static function stripe(): PasarelaStripe
    {
        return new PasarelaStripe(
            (string) Configuracion::obtener('stripe.secreta'),
            (string) Configuracion::obtener('stripe.webhook'),
            (string) Configuracion::obtener('stripe.publica')
        );
    }
}
