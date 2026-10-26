<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Cita médica (dueño: CU09). Guarda su propio intervalo, estado e importe acordado; la exclusión
 * ex_cita_sin_solapamiento de PostgreSQL impide dos citas no canceladas superpuestas del mismo médico.
 */
final class Ecita_medica extends Modelo
{
    protected const TABLA = 'cita_medica';

    public const PROGRAMADA = 1;
    public const CONFIRMADA = 2;
    public const ATENDIDA = 3;
    public const CANCELADA = 4;
    public const INASISTENCIA = 5;

    private const LISTADO = "SELECT c.id, c.codigo, c.inicio, c.fin, c.modalidad, c.importe_acordado, c.moneda, c.motivo, c.fecha_creacion,
                                    c.paciente_id, c.medico_id, c.especialidad_id, c.estado_cita_id,
                                    ec.nombre AS estado, e.nombre AS especialidad,
                                    m.codigo AS codigo_medico,
                                    CASE WHEN dm.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dm.nombres || ' ' || dm.apellidos AS medico,
                                    dp.nombres || ' ' || dp.apellidos AS paciente, pa.codigo AS codigo_paciente, dp.ci AS ci_paciente
                               FROM cita_medica c
                               JOIN estado_cita ec ON ec.id = c.estado_cita_id
                               JOIN especialidad e ON e.id = c.especialidad_id
                               JOIN medico m ON m.id = c.medico_id
                               JOIN datos_personales dm ON dm.id = c.medico_id
                               JOIN paciente pa ON pa.id = c.paciente_id
                               JOIN datos_personales dp ON dp.id = c.paciente_id";

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(self::LISTADO . ' WHERE c.id = ?', [$id]);
    }

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

    /** Citas no canceladas de un médico en una fecha: CU07 las muestra antes de bloquear el día. */
    public function noCanceladasEnFecha(int $medicoId, string $fecha): array
    {
        return $this->consultar(
            self::LISTADO . ' WHERE c.medico_id = ? AND c.estado_cita_id <> 4 AND c.inicio::date = CAST(? AS date) ORDER BY c.inicio',
            [$medicoId, $fecha]
        );
    }

    /** Intervalos ocupados (citas no canceladas) de un médico entre dos instantes (CU08, RN09). */
    public function intervalosOcupados(int $medicoId, string $desde, string $hasta): array
    {
        return $this->consultar(
            'SELECT id, inicio, fin FROM cita_medica
              WHERE medico_id = ? AND estado_cita_id <> 4 AND inicio < CAST(? AS timestamptz) AND fin > CAST(? AS timestamptz)',
            [$medicoId, $hasta, $desde]
        );
    }

    /** El paciente ya tiene otra cita vigente que se cruza con el intervalo. */
    public function pacienteTieneCruce(int $pacienteId, string $inicio, string $fin, ?int $excluirId = null): ?string
    {
        $codigo = $this->valor(
            'SELECT codigo FROM cita_medica
              WHERE paciente_id = ? AND estado_cita_id IN (1, 2) AND id <> ?
                AND inicio < CAST(? AS timestamptz) AND fin > CAST(? AS timestamptz) LIMIT 1',
            [$pacienteId, $excluirId ?? 0, $fin, $inicio]
        );
        return $codigo === false ? null : (string) $codigo;
    }

    /** Código correlativo CM-0001; el bloqueo serializa la generación entre reservas simultáneas. */
    public function siguienteCodigo(): string
    {
        // El pooler de Neon aborta la transacción si el resultado de este SELECT no se lee.
        $this->consultar("SELECT pg_advisory_xact_lock(hashtext('cita_medica.codigo'))");
        $numero = (int) $this->valor("SELECT COALESCE(max(substring(codigo FROM 4)::int), 0) + 1 FROM cita_medica WHERE codigo ~ '^CM-[0-9]+$'");
        return sprintf('CM-%04d', $numero);
    }

    /**
     * Serializa las operaciones de agenda de un médico dentro de la transacción actual: dos reservas
     * o reprogramaciones simultáneas del mismo médico se ejecutan una después de la otra.
     */
    public function bloquearAgendaDe(int $medicoId): void
    {
        $this->consultar('SELECT pg_advisory_xact_lock(1, CAST(? AS integer))', [$medicoId]);
    }

    /** Relee la cita bloqueando su fila hasta el fin de la transacción (evita cambios simultáneos). */
    public function paraActualizar(int $id): ?array
    {
        return $this->primero('SELECT id, codigo, paciente_id, medico_id, especialidad_id, estado_cita_id, inicio, fin FROM cita_medica WHERE id = ? FOR UPDATE', [$id]);
    }

    public function cambiarIntervalo(int $id, string $inicio, string $fin): void
    {
        $this->ejecutar(
            'UPDATE cita_medica SET inicio = CAST(? AS timestamptz), fin = CAST(? AS timestamptz) WHERE id = ?',
            [$inicio, $fin, $id]
        );
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
