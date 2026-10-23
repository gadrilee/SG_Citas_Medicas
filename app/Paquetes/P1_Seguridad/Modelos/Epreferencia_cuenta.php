<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Preferencias de notificación y privacidad de una cuenta (1:1 con usuario). */
final class Epreferencia_cuenta extends Modelo
{
    protected const TABLA = 'preferencia_cuenta';
    protected const CLAVE = 'usuario_id';

    public function deUsuario(int $usuarioId): array
    {
        return $this->buscarPorId($usuarioId) ?? ['usuario_id' => $usuarioId, 'notificaciones' => true, 'mostrar_contacto_al_medico' => false];
    }

    public function guardar(int $usuarioId, bool $notificaciones, bool $mostrarContacto): void
    {
        $this->ejecutar(
            'INSERT INTO preferencia_cuenta (usuario_id, notificaciones, mostrar_contacto_al_medico) VALUES (?, ?, ?)
             ON CONFLICT (usuario_id) DO UPDATE SET notificaciones = EXCLUDED.notificaciones,
                                                    mostrar_contacto_al_medico = EXCLUDED.mostrar_contacto_al_medico',
            [$usuarioId, $notificaciones ? 'true' : 'false', $mostrarContacto ? 'true' : 'false']
        );
    }
}
