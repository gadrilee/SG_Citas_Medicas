<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Días completos sin atención de un médico (ausencia, feriado, capacitación). Único por médico y fecha. */
final class Ebloqueo_agenda extends Modelo
{
    protected const TABLA = 'bloqueo_agenda';

    public function proximosDe(int $medicoId): array
    {
        return $this->consultar(
            'SELECT id, fecha, motivo FROM bloqueo_agenda WHERE medico_id = ? AND fecha >= current_date ORDER BY fecha',
            [$medicoId]
        );
    }

    /** CU13 · Bloqueo de un día concreto (con su motivo) o null. */
    public function deFecha(int $medicoId, string $fecha): ?array
    {
        return $this->primero('SELECT id, fecha, motivo FROM bloqueo_agenda WHERE medico_id = ? AND fecha = CAST(? AS date)', [$medicoId, $fecha]);
    }

    public function existe(int $medicoId, string $fecha): bool
    {
        return (bool) $this->valor('SELECT 1 FROM bloqueo_agenda WHERE medico_id = ? AND fecha = CAST(? AS date)', [$medicoId, $fecha]);
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM bloqueo_agenda WHERE id = ?', [$id]);
    }
}
