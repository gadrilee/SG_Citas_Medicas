<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Indicaciones de tratamiento de una consulta (CU17). */
final class Etratamiento extends Modelo
{
    protected const TABLA = 'tratamiento';

    /** @param array $fila indicaciones, fecha_inicio, fecha_fin (fechas opcionales) */
    public function registrar(int $consultaId, array $fila): void
    {
        $this->insertar([
            'consulta_id'  => $consultaId,
            'indicaciones' => $fila['indicaciones'],
            'fecha_inicio' => $fila['fecha_inicio'] ?: null,
            'fecha_fin'    => $fila['fecha_fin'] ?: null,
        ]);
    }

    public function deConsulta(int $consultaId): array
    {
        return $this->consultar('SELECT * FROM tratamiento WHERE consulta_id = ? ORDER BY id', [$consultaId]);
    }
}
