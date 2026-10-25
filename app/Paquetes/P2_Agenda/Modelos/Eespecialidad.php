<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Catálogo de especialidades con su tarifa de consulta (CU06). */
final class Eespecialidad extends Modelo
{
    protected const TABLA = 'especialidad';

    private const CON_MEDICOS = 'SELECT e.id, e.nombre, e.descripcion, e.activo, e.importe_consulta,
                                        (SELECT count(*) FROM medico_especialidad me JOIN medico m ON m.id = me.medico_id
                                          WHERE me.especialidad_id = e.id AND me.activo AND m.activo) AS medicos
                                   FROM especialidad e';

    public function activas(): array
    {
        return $this->consultar(self::CON_MEDICOS . ' WHERE e.activo ORDER BY e.nombre');
    }

    /** Todas, activas primero (vista de administración). */
    public function todas(): array
    {
        return $this->consultar(self::CON_MEDICOS . ' ORDER BY e.activo DESC, e.nombre');
    }

    /** Nombre normalizado (sin distinguir mayúsculas ni espacios extremos), como el índice único. */
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM especialidad WHERE lower(btrim(nombre)) = lower(btrim(?)) AND id <> ?',
            [$nombre, $excluirId ?? 0]
        );
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE especialidad SET activo = ? WHERE id = ?', [$activo ? 'true' : 'false', $id]);
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
