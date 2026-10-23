<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;

/** Protección contra falsificación de solicitudes: un token por sesión en cada formulario. */
final class Csrf
{
    public static function token(): string
    {
        $token = Sesion::obtener('_csrf');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Sesion::poner('_csrf', $token);
        }
        return $token;
    }

    public static function validar(Solicitud $solicitud): void
    {
        $enviado = (string) ($solicitud->entrada('_csrf') ?? $solicitud->cabecera('X-CSRF-Token') ?? '');
        if ($enviado === '' || !hash_equals(self::token(), $enviado)) {
            throw new ExcepcionAcceso('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
    }
}
