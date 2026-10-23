<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Cita médica (dueño: CU09). En el ciclo 1 de P1 solo se usan consultas de lectura para fichas,
 * paneles y avisos; la reserva y sus reglas se implementan con CU08–CU10.
 */
final class Ecita_medica extends Modelo
{
    protected const TABLA = 'cita_medica';

    private const LISTADO = "SELECT c.id, c.codigo, c.inicio, c.fin, c.modalidad, c.importe_acordado, c.moneda, c.motivo,
                                    ec.nombre AS estado, e.nombre AS especialidad,
                                    m.codigo AS codigo_medico,
                                    CASE WHEN dm.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dm.nombres || ' ' || dm.apellidos AS medico,
                                    dp.nombres || ' ' || dp.apellidos AS paciente, pa.codigo AS codigo_paciente
                               FROM cita_medica c
                               JOIN estado_cita ec ON ec.id = c.estado_cita_id
                               JOIN especialidad e ON e.id = c.especialidad_id
                               JOIN medico m ON m.id = c.medico_id
                               JOIN datos_personales dm ON dm.id = c.medico_id
                               JOIN paciente pa ON pa.id = c.paciente_id
                               JOIN datos_personales dp ON dp.id = c.paciente_id";

    public function dePaciente(int $pacienteId): array
    {
        return $this->consultar(self::LISTADO . ' WHERE c.paciente_id = ? ORDER BY c.inicio DESC', [$pacienteId]);
    }

    /** Próximas citas vigentes (programadas o confirmadas), opcionalmente de un médico o paciente. */
    public function proximas(?int $medicoId = null, ?int $pacienteId = null, int $cantidad = 8): array
    {
        $condiciones = ['c.estado_cita_id IN (1, 2)', 'c.fin >= now()'];
        $parametros = [];
        if ($medicoId) {
            $condiciones[] = 'c.medico_id = :medico';
            $parametros['medico'] = $medicoId;
        }
        if ($pacienteId) {
            $condiciones[] = 'c.paciente_id = :paciente';
            $parametros['paciente'] = $pacienteId;
        }
        return $this->consultar(
            self::LISTADO . ' WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY c.inicio LIMIT ' . $cantidad,
            $parametros
        );
    }

    /** Citas vigentes futuras de un médico: CU05 las lista antes de desactivarlo. */
    public function futurasDeMedico(int $medicoId): array
    {
        return $this->consultar(self::LISTADO . ' WHERE c.medico_id = ? AND c.estado_cita_id IN (1, 2) AND c.inicio > now() ORDER BY c.inicio', [$medicoId]);
    }

    /** Indicadores del panel: hoy, vigentes futuras y del mes actual por estado. */
    public function indicadores(?int $medicoId = null): array
    {
        $filtro = $medicoId ? ' AND medico_id = ' . (int) $medicoId : '';
        return $this->primero(
            "SELECT count(*) FILTER (WHERE inicio::date = current_date AND estado_cita_id <> 4)        AS hoy,
                    count(*) FILTER (WHERE inicio::date = current_date AND estado_cita_id = 2)         AS hoy_confirmadas,
                    count(*) FILTER (WHERE inicio > now() AND estado_cita_id IN (1, 2))                AS vigentes,
                    count(*) FILTER (WHERE date_trunc('month', inicio) = date_trunc('month', now()) AND estado_cita_id = 3) AS atendidas_mes,
                    count(*) FILTER (WHERE date_trunc('month', inicio) = date_trunc('month', now()) AND estado_cita_id = 4) AS canceladas_mes,
                    count(*) FILTER (WHERE date_trunc('month', inicio) = date_trunc('month', now()) AND estado_cita_id = 5) AS inasistencias_mes
               FROM cita_medica WHERE TRUE" . $filtro
        ) ?? [];
    }
}
