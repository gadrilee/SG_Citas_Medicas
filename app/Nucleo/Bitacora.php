<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

/** Registro de auditoría que consultará CU03. Los casos de uso lo llaman después de cada cambio. */
final class Bitacora
{
    private static ?string $ip = null;

    public static function definirIp(?string $ip): void
    {
        self::$ip = $ip;
    }

    /**
     * @param string $accion   verbo corto: 'reservar', 'cancelar', 'iniciar_sesion'…
     * @param string $entidad  tabla afectada: 'cita_medica', 'usuario'…
     */
    public static function registrar(string $accion, string $entidad, int $registroId, ?int $usuarioId = null): void
    {
        $usuarioId ??= Sesion::usuario()['id'] ?? null;
        if ($usuarioId === null) {
            return;
        }
        BaseDatos::conexion()
            ->prepare('INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, ip) VALUES (?, ?, ?, ?, ?)')
            ->execute([$usuarioId, $accion, $entidad, $registroId, self::$ip]);
    }
}
