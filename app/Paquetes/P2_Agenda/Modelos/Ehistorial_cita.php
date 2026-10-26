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

    /** CU11 y CU12: estado anterior → nuevo; cancelar exige motivo (ck_hist_observacion). */
    public function registrarCambioEstado(array $cita, int $usuarioId, int $estadoNuevo, ?string $observacion = null): void
    {
        $this->insertar([
            'cita_id'            => (int) $cita['id'],
            'usuario_id'         => $usuarioId,
            'tipo_evento'        => 'cambio_estado',
            'estado_anterior_id' => (int) $cita['estado_cita_id'],
            'estado_nuevo_id'    => $estadoNuevo,
            'inicio_nuevo'       => $cita['inicio'],
            'fin_nuevo'          => $cita['fin'],
            'observacion'        => $observacion,
        ]);
    }

    private const EVENTOS = 'SELECT h.cita_id, h.tipo_evento, h.inicio_anterior, h.fin_anterior, h.inicio_nuevo, h.fin_nuevo, h.observacion, h.fecha_hora,
                                    ea.nombre AS estado_anterior, en.nombre AS estado_nuevo, u.nombre_usuario
                               FROM historial_cita h
                               JOIN usuario u ON u.id = h.usuario_id
                               JOIN estado_cita en ON en.id = h.estado_nuevo_id
                               LEFT JOIN estado_cita ea ON ea.id = h.estado_anterior_id';

    public function deCita(int $citaId): array
    {
        return $this->consultar(self::EVENTOS . ' WHERE h.cita_id = ? ORDER BY h.fecha_hora, h.id', [$citaId]);
    }

    /** CU14 · Eventos de varias citas en una sola consulta: [cita_id => [eventos…]]. */
    public function deCitas(array $citaIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $citaIds)));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $agrupados = [];
        foreach ($this->consultar(self::EVENTOS . " WHERE h.cita_id IN ($marcas) ORDER BY h.fecha_hora, h.id", $ids) as $evento) {
            $agrupados[(int) $evento['cita_id']][] = $evento;
        }
        return $agrupados;
    }
}
