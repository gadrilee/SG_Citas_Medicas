<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Consulta médica de una cita (dueño: CU17). Una cita tiene como máximo una consulta (UNIQUE cita_id). */
final class Econsulta extends Modelo
{
    protected const TABLA = 'consulta';

    public function registrar(int $citaId, string $observaciones, string $diagnostico): int
    {
        return $this->insertar([
            'cita_id'       => $citaId,
            'fecha_hora'    => date(DATE_ATOM),
            'observaciones' => $observaciones === '' ? null : $observaciones,
            'diagnostico'   => $diagnostico === '' ? null : $diagnostico,
        ]);
    }

    public function deCita(int $citaId): ?array
    {
        return $this->primero('SELECT * FROM consulta WHERE cita_id = ?', [$citaId]);
    }

    /** CU16 paso 3: consultas anteriores del paciente con su médico y sus tratamientos. */
    public function dePaciente(int $pacienteId, int $cantidad = 10): array
    {
        return $this->consultar(
            "SELECT co.id, co.fecha_hora, co.observaciones, co.diagnostico, c.id AS cita_id, c.codigo AS cita, e.nombre AS especialidad,
                    CASE WHEN dm.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dm.nombres || ' ' || dm.apellidos AS medico,
                    (SELECT string_agg(t.indicaciones, E'\\n' ORDER BY t.id) FROM tratamiento t WHERE t.consulta_id = co.id) AS tratamientos
               FROM consulta co
               JOIN cita_medica c ON c.id = co.cita_id
               JOIN especialidad e ON e.id = c.especialidad_id
               JOIN datos_personales dm ON dm.id = c.medico_id
              WHERE c.paciente_id = ?
              ORDER BY co.fecha_hora DESC
              LIMIT " . $cantidad,
            [$pacienteId]
        );
    }
}
