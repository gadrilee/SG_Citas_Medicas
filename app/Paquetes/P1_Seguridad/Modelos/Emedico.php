<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Médico: subtipo de datos_personales con código y registro profesional únicos. */
final class Emedico extends Modelo
{
    protected const TABLA = 'medico';

    private const ESPECIALIDADES = "(SELECT string_agg(e.nombre, ' · ' ORDER BY e.nombre)
                                       FROM medico_especialidad me JOIN especialidad e ON e.id = me.especialidad_id
                                      WHERE me.medico_id = m.id AND me.activo)";

    public function siguienteCodigo(): string
    {
        $this->consultar('SELECT id FROM perfil WHERE id = 1 FOR UPDATE');
        $numero = (int) $this->valor("SELECT COALESCE(max(substring(codigo FROM 3)::int), 0) + 1 FROM medico WHERE codigo ~ '^M-[0-9]+$'");
        return sprintf('M-%03d', $numero);
    }

    public function registrar(int $id, string $codigo, string $registro): void
    {
        $this->ejecutar('INSERT INTO medico (id, codigo, nro_registro_profesional) VALUES (?, ?, ?)', [$id, $codigo, $registro]);
    }

    public function listar(string $buscar, ?int $especialidadId): array
    {
        $condiciones = ['TRUE'];
        $parametros = [];
        if ($buscar !== '') {
            $condiciones[] = "((dp.nombres || ' ' || dp.apellidos) ILIKE :b1 OR m.codigo ILIKE :b2 OR " . self::ESPECIALIDADES . ' ILIKE :b3)';
            $parametros += ['b1' => "%$buscar%", 'b2' => "%$buscar%", 'b3' => "%$buscar%"];
        }
        if ($especialidadId) {
            $condiciones[] = 'EXISTS (SELECT 1 FROM medico_especialidad me WHERE me.medico_id = m.id AND me.especialidad_id = :esp AND me.activo)';
            $parametros['esp'] = $especialidadId;
        }
        return $this->consultar(
            'SELECT m.id, m.codigo, m.nro_registro_profesional, m.activo, dp.nombres, dp.apellidos, dp.telefono, dp.correo,
                    ' . self::ESPECIALIDADES . ' AS especialidades
               FROM medico m JOIN datos_personales dp ON dp.id = m.id
              WHERE ' . implode(' AND ', $condiciones) . '
              ORDER BY m.activo DESC, dp.apellidos, dp.nombres',
            $parametros
        );
    }

    /** Médicos activos para la portada y la barra de reserva, con sus especialidades. */
    public function activosConEspecialidades(): array
    {
        return $this->consultar(
            "SELECT m.id, m.codigo, m.nro_registro_profesional, dp.nombres, dp.apellidos,
                    CASE WHEN dp.sexo = 'Femenino' THEN 'Dra. ' ELSE 'Dr. ' END || dp.nombres || ' ' || dp.apellidos AS nombre,
                    " . self::ESPECIALIDADES . " AS especialidades,
                    (SELECT string_agg(me.especialidad_id::text, ',') FROM medico_especialidad me WHERE me.medico_id = m.id AND me.activo) AS especialidades_ids,
                    (SELECT string_agg(DISTINCT h.dia_semana::text, ',') FROM horario_atencion h
                      WHERE h.medico_id = m.id AND h.activo AND (h.vigente_hasta IS NULL OR h.vigente_hasta >= current_date)) AS dias_atencion,
                    (SELECT string_agg(b.fecha::text, ',' ORDER BY b.fecha) FROM bloqueo_agenda b
                      WHERE b.medico_id = m.id AND b.fecha >= current_date) AS bloqueos
               FROM medico m JOIN datos_personales dp ON dp.id = m.id
              WHERE m.activo
              ORDER BY m.codigo"
        );
    }

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(
            'SELECT m.id, m.codigo, m.nro_registro_profesional, m.activo, dp.* FROM medico m JOIN datos_personales dp ON dp.id = m.id WHERE m.id = ?',
            [$id]
        );
    }

    public function existeRegistro(string $registro, ?int $excluirId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM medico WHERE upper(btrim(nro_registro_profesional)) = upper(btrim(?)) AND id <> ?',
            [$registro, $excluirId ?? 0]
        );
    }

    public function actualizarRegistro(int $id, string $registro): void
    {
        $this->ejecutar('UPDATE medico SET nro_registro_profesional = ? WHERE id = ?', [$registro, $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE medico SET activo = ? WHERE id = ?', [$activo ? 'true' : 'false', $id]);
    }

    public function contarActivos(): int
    {
        return (int) $this->valor('SELECT count(*) FROM medico WHERE activo');
    }
}
