<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Especialidades que ejerce cada médico (PK compuesta). Las citas referencian esta pareja,
 * por eso una asignación se desactiva en lugar de borrarse.
 */
final class Emedico_especialidad extends Modelo
{
    protected const TABLA = 'medico_especialidad';

    public function idsActivosDe(int $medicoId): array
    {
        return array_map('intval', array_column(
            $this->consultar('SELECT especialidad_id FROM medico_especialidad WHERE medico_id = ? AND activo', [$medicoId]),
            'especialidad_id'
        ));
    }

    /** La pareja existe, está activa y tanto el médico como la especialidad están activos. */
    public function habilitada(int $medicoId, int $especialidadId): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM medico_especialidad me
               JOIN medico m ON m.id = me.medico_id
               JOIN especialidad e ON e.id = me.especialidad_id
              WHERE me.medico_id = ? AND me.especialidad_id = ? AND me.activo AND m.activo AND e.activo',
            [$medicoId, $especialidadId]
        );
    }

    public function asignar(int $medicoId, array $especialidadIds): void
    {
        $this->ejecutar('UPDATE medico_especialidad SET activo = FALSE WHERE medico_id = ?', [$medicoId]);
        $consulta = $this->bd->prepare(
            'INSERT INTO medico_especialidad (medico_id, especialidad_id) VALUES (?, ?)
             ON CONFLICT (medico_id, especialidad_id) DO UPDATE SET activo = TRUE'
        );
        foreach ($especialidadIds as $especialidadId) {
            $consulta->execute([$medicoId, $especialidadId]);
        }
    }
}
