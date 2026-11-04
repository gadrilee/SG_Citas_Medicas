<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Estudio de laboratorio (CU18 lo solicita, CU19 publica su resultado).
 * Estados: pendiente → en_proceso → disponible. Un estudio disponible ya no cambia (E4 de CU19).
 */
final class Eestudio_laboratorio extends Modelo
{
    protected const TABLA = 'estudio_laboratorio';

    public const PENDIENTE = 'pendiente';
    public const EN_PROCESO = 'en_proceso';
    public const DISPONIBLE = 'disponible';

    private const TRANSICIONES = [
        self::PENDIENTE  => [self::EN_PROCESO, self::DISPONIBLE],
        self::EN_PROCESO => [self::DISPONIBLE],
    ];

    private const COLUMNAS = "es.*, c.id AS cita_id, c.codigo AS cita, c.paciente_id, c.medico_id,
                              dp.nombres || ' ' || dp.apellidos AS paciente, pa.codigo AS codigo_paciente,
                              CASE WHEN dm.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dm.nombres || ' ' || dm.apellidos AS medico,
                              up.nombre_usuario AS publicado_por_usuario";

    private const DESDE = 'FROM estudio_laboratorio es
                           JOIN consulta co ON co.id = es.consulta_id
                           JOIN cita_medica c ON c.id = co.cita_id
                           JOIN paciente pa ON pa.id = c.paciente_id
                           JOIN datos_personales dp ON dp.id = c.paciente_id
                           JOIN datos_personales dm ON dm.id = c.medico_id
                           LEFT JOIN usuario up ON up.id = es.publicado_por';

    private const LISTADO = 'SELECT ' . self::COLUMNAS . ' ' . self::DESDE;

    public static function transicionPermitida(string $desde, string $hacia): bool
    {
        return in_array($hacia, self::TRANSICIONES[$desde] ?? [], true);
    }

    /** CU18 paso 4: cada estudio queda pendiente con su fecha de solicitud. */
    public function registrar(int $consultaId, string $nombre, string $indicaciones): void
    {
        $this->insertar([
            'consulta_id'    => $consultaId,
            'nombre_estudio' => $nombre,
            'indicaciones'   => $indicaciones === '' ? null : $indicaciones,
        ]);
    }

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(self::LISTADO . ' WHERE es.id = ?', [$id]);
    }

    /**
     * CU19 · Datos del informe formal: paciente, solicitud (médico y especialidad) y responsable que
     * publicó el resultado con su matrícula profesional.
     */
    public function paraInforme(int $id): ?array
    {
        return $this->primero(
            'SELECT ' . self::COLUMNAS . ",
                    dp.ci, dp.fecha_nacimiento, dp.sexo, dp.telefono,
                    e.nombre AS especialidad, co.fecha_hora AS fecha_consulta,
                    CASE WHEN dr.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dr.nombres || ' ' || dr.apellidos AS responsable,
                    mr.nro_registro_profesional AS matricula_responsable
               " . self::DESDE . '
               JOIN especialidad e ON e.id = c.especialidad_id
               LEFT JOIN datos_personales dr ON dr.id = up.datos_personales_id
               LEFT JOIN medico mr ON mr.id = up.datos_personales_id
              WHERE es.id = ?',
            [$id]
        );
    }

    public function paraActualizar(int $id): ?array
    {
        return $this->primero('SELECT id, estado FROM estudio_laboratorio WHERE id = ? FOR UPDATE', [$id]);
    }

    public function deConsulta(int $consultaId): array
    {
        return $this->consultar(self::LISTADO . ' WHERE es.consulta_id = ? ORDER BY es.id', [$consultaId]);
    }

    /** CU19 paso 1: estudios de los pacientes del médico, por estado ('pendientes' = pendiente y en proceso). */
    public function delMedico(int $medicoId, string $estado, string $buscar, int $pagina): array
    {
        $condiciones = ['c.medico_id = :medico'];
        $parametros = ['medico' => $medicoId];
        if ($estado === 'pendientes') {
            $condiciones[] = "es.estado IN ('pendiente', 'en_proceso')";
        } elseif ($estado !== '') {
            $condiciones[] = 'es.estado = :estado';
            $parametros['estado'] = $estado;
        }
        if ($buscar !== '') {
            $condiciones[] = "((dp.nombres || ' ' || dp.apellidos) ILIKE :b1 OR pa.codigo ILIKE :b2 OR es.nombre_estudio ILIKE :b3 OR c.codigo ILIKE :b4)";
            $parametros += ['b1' => "%$buscar%", 'b2' => "%$buscar%", 'b3' => "%$buscar%", 'b4' => "%$buscar%"];
        }
        return $this->paginar(
            self::COLUMNAS,
            self::DESDE . ' WHERE ' . implode(' AND ', $condiciones),
            $parametros,
            "CASE es.estado WHEN 'pendiente' THEN 1 WHEN 'en_proceso' THEN 2 ELSE 3 END, es.solicitado_en DESC",
            $pagina,
            12
        );
    }

    /** CU19 paso 6 y E3: solo los estudios publicados del propio paciente. */
    public function publicadosDePaciente(int $pacienteId): array
    {
        return $this->consultar(self::LISTADO . " WHERE c.paciente_id = ? AND es.estado = 'disponible' ORDER BY es.publicado_en DESC", [$pacienteId]);
    }

    /** Estudios aún no publicados del paciente: se muestran sin resultado (A1 de CU19). */
    public function enCursoDePaciente(int $pacienteId): array
    {
        return $this->consultar(self::LISTADO . " WHERE c.paciente_id = ? AND es.estado <> 'disponible' ORDER BY es.solicitado_en DESC", [$pacienteId]);
    }

    public function contarPorEstado(int $medicoId): array
    {
        $filas = $this->consultar(
            'SELECT es.estado, count(*) AS total ' . self::DESDE . ' WHERE c.medico_id = ? GROUP BY es.estado',
            [$medicoId]
        );
        return array_column($filas, 'total', 'estado') + [self::PENDIENTE => 0, self::EN_PROCESO => 0, self::DISPONIBLE => 0];
    }

    public function marcarEnProceso(int $id): void
    {
        $this->ejecutar("UPDATE estudio_laboratorio SET estado = 'en_proceso' WHERE id = ?", [$id]);
    }

    /** CU19 paso 5: queda disponible con fecha de publicación y responsable. */
    public function publicar(int $id, ?string $texto, ?string $archivo, int $usuarioId): void
    {
        $this->ejecutar(
            "UPDATE estudio_laboratorio
                SET estado = 'disponible', resultado_texto = ?, archivo_referencia = ?, publicado_en = now(), publicado_por = ?
              WHERE id = ?",
            [$texto, $archivo, $usuarioId, $id]
        );
    }
}
