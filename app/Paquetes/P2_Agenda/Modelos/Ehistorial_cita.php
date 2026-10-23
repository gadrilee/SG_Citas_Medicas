<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Eventos de una cita: creación, cambios de estado y reprogramaciones. Se escriben en la misma
 * transacción que el cambio de la cita y nunca se modifican.
 */
final class Ehistorial_cita extends Modelo
{
    protected const TABLA = 'historial_cita';

    public function registrarCreacion(int $citaId, int $usuarioId, string $inicio, string $fin): void
    {
        $this->insertar([
            'cita_id'         => $citaId,
            'usuario_id'      => $usuarioId,
            'tipo_evento'     => 'creacion',
            'estado_nuevo_id' => Ecita_medica::PROGRAMADA,
            'inicio_nuevo'    => $inicio,
            'fin_nuevo'       => $fin,
        ]);
    }

    /** El estado se conserva; el intervalo anterior y el motivo quedan registrados (RF08). */
    public function registrarReprogramacion(array $cita, int $usuarioId, string $inicio, string $fin, string $motivo): void
    {
        $this->insertar([
            'cita_id'            => (int) $cita['id'],
            'usuario_id'         => $usuarioId,
            'tipo_evento'        => 'reprogramacion',
            'estado_anterior_id' => (int) $cita['estado_cita_id'],
            'estado_nuevo_id'    => (int) $cita['estado_cita_id'],
            'inicio_anterior'    => $cita['inicio'],
            'fin_anterior'       => $cita['fin'],
            'inicio_nuevo'       => $inicio,
            'fin_nuevo'          => $fin,
            'observacion'        => $motivo,
        ]);
    }

    public function deCita(int $citaId): array
    {
        return $this->consultar(
            'SELECT h.tipo_evento, h.inicio_anterior, h.fin_anterior, h.inicio_nuevo, h.fin_nuevo, h.observacion, h.fecha_hora,
                    ea.nombre AS estado_anterior, en.nombre AS estado_nuevo, u.nombre_usuario
               FROM historial_cita h
               JOIN usuario u ON u.id = h.usuario_id
               JOIN estado_cita en ON en.id = h.estado_nuevo_id
               LEFT JOIN estado_cita ea ON ea.id = h.estado_anterior_id
              WHERE h.cita_id = ? ORDER BY h.fecha_hora, h.id',
            [$citaId]
        );
    }
}
