<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Paciente: subtipo de datos_personales (PK compartida). */
final class Epaciente extends Modelo
{
    protected const TABLA = 'paciente';

    /** Código correlativo P-0001; el bloqueo evita que dos registros simultáneos tomen el mismo. */
    public function siguienteCodigo(): string
    {
        $this->consultar('SELECT id FROM perfil WHERE id = 1 FOR UPDATE');
        $numero = (int) $this->valor("SELECT COALESCE(max(substring(codigo FROM 3)::int), 0) + 1 FROM paciente WHERE codigo ~ '^P-[0-9]+$'");
        return sprintf('P-%04d', $numero);
    }

    public function registrar(int $id, string $codigo): void
    {
        $this->ejecutar('INSERT INTO paciente (id, codigo) VALUES (?, ?)', [$id, $codigo]);
    }

    /** Lista con búsqueda por CI, nombre, apellido o código y filtro de estado. */
    public function listar(string $buscar, ?bool $activo, int $pagina): array
    {
        $condiciones = ['TRUE'];
        $parametros = [];
        if ($buscar !== '') {
            $condiciones[] = "(dp.ci ILIKE :b1 OR (dp.nombres || ' ' || dp.apellidos) ILIKE :b2 OR p.codigo ILIKE :b3)";
            $parametros += ['b1' => "%$buscar%", 'b2' => "%$buscar%", 'b3' => "%$buscar%"];
        }
        if ($activo !== null) {
            $condiciones[] = 'p.activo = :activo';
            $parametros['activo'] = $activo ? 'true' : 'false';
        }
        return $this->paginar(
            "p.id, p.codigo, p.activo, dp.ci, dp.nombres, dp.apellidos, dp.telefono,
             (SELECT max(c.inicio) FROM cita_medica c WHERE c.paciente_id = p.id) AS ultima_cita",
            'FROM paciente p JOIN datos_personales dp ON dp.id = p.id WHERE ' . implode(' AND ', $condiciones),
            $parametros,
            'dp.apellidos, dp.nombres',
            $pagina
        );
    }

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(
            'SELECT p.id, p.codigo, p.activo, p.fecha_registro, dp.*,
                    (SELECT count(*) FROM cita_medica c WHERE c.paciente_id = p.id AND c.estado_cita_id = 3) AS atendidas,
                    (SELECT count(*) FROM cita_medica c WHERE c.paciente_id = p.id AND c.estado_cita_id = 5) AS inasistencias,
                    u.nombre_usuario
               FROM paciente p
               JOIN datos_personales dp ON dp.id = p.id
               LEFT JOIN usuario u ON u.datos_personales_id = p.id
              WHERE p.id = ?',
            [$id]
        );
    }

    /** Pacientes activos para elegir en la reserva hecha por recepción (CU09). */
    public function activosParaReserva(): array
    {
        return $this->consultar(
            "SELECT p.id, p.codigo, dp.ci, dp.nombres || ' ' || dp.apellidos AS nombre, dp.telefono
               FROM paciente p JOIN datos_personales dp ON dp.id = p.id
              WHERE p.activo ORDER BY dp.apellidos, dp.nombres LIMIT 500"
        );
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE paciente SET activo = ? WHERE id = ?', [$activo ? 'true' : 'false', $id]);
    }

    /** CU15 · Pacientes registrados en un período, con su cantidad de citas. */
    public function registradosEntre(string $desde, string $hasta): array
    {
        return $this->consultar(
            "SELECT p.id, p.codigo, p.activo, p.fecha_registro, dp.ci, dp.nombres || ' ' || dp.apellidos AS nombre, dp.telefono,
                    (SELECT count(*) FROM cita_medica c WHERE c.paciente_id = p.id) AS citas
               FROM paciente p JOIN datos_personales dp ON dp.id = p.id
              WHERE p.fecha_registro >= CAST(:desde AS date) AND p.fecha_registro < CAST(:hasta AS date) + 1
              ORDER BY p.fecha_registro",
            ['desde' => $desde, 'hasta' => $hasta]
        );
    }

    public function contarActivos(): int
    {
        return (int) $this->valor('SELECT count(*) FROM paciente WHERE activo');
    }
}
