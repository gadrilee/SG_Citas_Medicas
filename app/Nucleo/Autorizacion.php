<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoAutenticado;

/**
 * Comprueba los privilegios del perfil del usuario (tablas perfil_privilegio y privilegio).
 * Cada ruta declara el privilegio que exige: null = pública, 'autenticado' = cualquier sesión.
 */
final class Autorizacion
{
    public const PUBLICA = null;
    public const AUTENTICADO = 'autenticado';

    public static function estaAutenticado(): bool
    {
        return Sesion::usuario() !== null;
    }

    public static function exigir(?string $privilegio): void
    {
        if ($privilegio === self::PUBLICA) {
            return;
        }
        if (!self::estaAutenticado()) {
            throw new ExcepcionNoAutenticado();
        }
        if ($privilegio !== self::AUTENTICADO && !self::tienePrivilegio($privilegio)) {
            throw new ExcepcionAcceso('No tienes permiso para realizar esta operación.');
        }
    }

    public static function tienePrivilegio(string $privilegio): bool
    {
        return in_array($privilegio, self::privilegios(), true);
    }

    /** Privilegios activos del perfil; se leen una vez por sesión y se invalidan al iniciar sesión. */
    public static function privilegios(): array
    {
        $usuario = Sesion::usuario();
        if (!$usuario) {
            return [];
        }
        $privilegios = Sesion::obtener('_privilegios');
        if ($privilegios === null) {
            $consulta = BaseDatos::conexion()->prepare(
                'SELECT p.nombre
                   FROM perfil_privilegio pp
                   JOIN privilegio p ON p.id = pp.privilegio_id
                   JOIN perfil pf ON pf.id = pp.perfil_id
                  WHERE pp.perfil_id = :perfil AND pp.activo AND pf.activo'
            );
            $consulta->execute(['perfil' => $usuario['perfil_id']]);
            $privilegios = $consulta->fetchAll(\PDO::FETCH_COLUMN);
            Sesion::poner('_privilegios', $privilegios);
        }
        return $privilegios;
    }

    public static function perfil(): ?string
    {
        return Sesion::usuario()['perfil'] ?? null;
    }
}
