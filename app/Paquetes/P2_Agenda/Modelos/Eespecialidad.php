<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Catálogo de especialidades (dueño: CU06). */
final class Eespecialidad extends Modelo
{
    protected const TABLA = 'especialidad';

    public function activas(): array
    {
        return $this->consultar(
            'SELECT e.id, e.nombre, e.descripcion,
                    (SELECT count(*) FROM medico_especialidad me JOIN medico m ON m.id = me.medico_id
                      WHERE me.especialidad_id = e.id AND me.activo AND m.activo) AS medicos
               FROM especialidad e WHERE e.activo ORDER BY e.nombre'
        );
    }

    /** Filtra una lista de ids dejando solo especialidades activas existentes. */
    public function idsActivos(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        return array_map('intval', array_column($this->consultar("SELECT id FROM especialidad WHERE activo AND id IN ($marcas)", $ids), 'id'));
    }
}
