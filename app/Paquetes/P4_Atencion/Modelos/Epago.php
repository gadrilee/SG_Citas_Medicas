<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Intentos de pago de una cita (dueño: CU20). Cada intento tiene una clave de idempotencia única y
 * el índice ux_pago_cita_activo permite como máximo un pago pendiente o aprobado por cita.
 * Estados: pendiente → aprobado | rechazado | cancelado; aprobado → reembolsado.
 */
final class Epago extends Modelo
{
    protected const TABLA = 'pago';

    private const COLUMNAS = "pg.*, pp.codigo AS pasarela_codigo, pp.nombre AS pasarela,
                              c.codigo AS cita, c.inicio AS cita_inicio, c.estado_cita_id, ec.nombre AS estado_cita,
                              c.paciente_id, dp.nombres || ' ' || dp.apellidos AS paciente, pa.codigo AS codigo_paciente,
                              e.nombre AS especialidad, u.nombre_usuario AS iniciado_por_usuario";

    private const DESDE = 'FROM pago pg
                           JOIN pasarela_pago pp ON pp.id = pg.pasarela_id
                           JOIN cita_medica c ON c.id = pg.cita_id
                           JOIN estado_cita ec ON ec.id = c.estado_cita_id
                           JOIN especialidad e ON e.id = c.especialidad_id
                           JOIN paciente pa ON pa.id = c.paciente_id
                           JOIN datos_personales dp ON dp.id = c.paciente_id
                           JOIN usuario u ON u.id = pg.iniciado_por';

    private const LISTADO = 'SELECT ' . self::COLUMNAS . ' ' . self::DESDE;

    /**
     * Paso 6 y E1: estado final según la respuesta ya verificada con la pasarela. Si dice «aprobado»
     * pero el importe o la moneda no coinciden con los del intento, el pago no se aprueba.
     */
    public static function estadoVerificado(array $pago, array $respuesta): string
    {
        if ($respuesta['estado'] !== 'aprobado') {
            return $respuesta['estado'];
        }
        $mismoImporte = abs((float) $respuesta['importe'] - (float) $pago['importe']) < 0.005;
        $mismaMoneda = strtoupper((string) $respuesta['moneda']) === strtoupper((string) $pago['moneda']);
        return $mismoImporte && $mismaMoneda ? 'aprobado' : 'rechazado';
    }

    /** Paso 2: intento pendiente con clave de idempotencia, importe y moneda de la cita. */
    public function registrarIntento(int $citaId, int $pasarelaId, int $usuarioId, string $importe, string $moneda): int
    {
        return (int) $this->valor(
            'INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, importe, moneda)
             VALUES (?, ?, ?, gen_random_uuid(), ?, ?) RETURNING id',
            [$citaId, $pasarelaId, $usuarioId, $importe, $moneda]
        );
    }

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(self::LISTADO . ' WHERE pg.id = ?', [$id]);
    }

    /** Relee el intento bloqueándolo: dos respuestas simultáneas de la pasarela se aplican una tras otra (E3). */
    public function paraActualizar(int $id): ?array
    {
        return $this->primero('SELECT * FROM pago WHERE id = ? FOR UPDATE', [$id]);
    }

    public function porReferencia(string $pasarelaCodigo, string $referencia): ?array
    {
        return $this->primero(
            'SELECT pg.* FROM pago pg JOIN pasarela_pago pp ON pp.id = pg.pasarela_id WHERE pp.codigo = ? AND pg.referencia_externa = ?',
            [$pasarelaCodigo, $referencia]
        );
    }

    /** Todos los intentos de una cita, el más reciente primero (A1: se conservan los rechazados). */
    public function deCita(int $citaId): array
    {
        return $this->consultar(self::LISTADO . ' WHERE pg.cita_id = ? ORDER BY pg.creado_en DESC, pg.id DESC', [$citaId]);
    }

    /** E2: el intento pendiente o aprobado de la cita, si existe. */
    public function vigenteDeCita(int $citaId): ?array
    {
        return $this->primero("SELECT * FROM pago WHERE cita_id = ? AND estado IN ('pendiente', 'aprobado')", [$citaId]);
    }

    public function asignarReferencia(int $id, string $referencia): void
    {
        $this->ejecutar('UPDATE pago SET referencia_externa = ? WHERE id = ?', [$referencia, $id]);
    }

    /** Paso 6: estado verificado con su fecha de verificación. */
    public function registrarEstado(int $id, string $estado): void
    {
        $this->ejecutar('UPDATE pago SET estado = ?, verificado_en = now() WHERE id = ?', [$estado, $id]);
    }

    public function dePaciente(int $pacienteId): array
    {
        return $this->consultar(
            'SELECT pg.creado_en, pg.importe, pg.moneda, pg.estado, pg.referencia_externa, c.codigo AS cita, pp.nombre AS pasarela
               FROM pago pg JOIN cita_medica c ON c.id = pg.cita_id JOIN pasarela_pago pp ON pp.id = pg.pasarela_id
              WHERE c.paciente_id = ? ORDER BY pg.creado_en DESC',
            [$pacienteId]
        );
    }

    /** Lista de caja: búsqueda por cita, paciente o referencia y filtro de estado. */
    public function buscar(string $buscar, string $estado, int $pagina): array
    {
        $condiciones = ['TRUE'];
        $parametros = [];
        if ($buscar !== '') {
            $condiciones[] = "(c.codigo ILIKE :b1 OR (dp.nombres || ' ' || dp.apellidos) ILIKE :b2 OR pa.codigo ILIKE :b3 OR pg.referencia_externa ILIKE :b4)";
            $parametros += ['b1' => "%$buscar%", 'b2' => "%$buscar%", 'b3' => "%$buscar%", 'b4' => "%$buscar%"];
        }
        if ($estado !== '') {
            $condiciones[] = 'pg.estado = :estado';
            $parametros['estado'] = $estado;
        }
        return $this->paginar(self::COLUMNAS, self::DESDE . ' WHERE ' . implode(' AND ', $condiciones), $parametros, 'pg.creado_en DESC, pg.id DESC', $pagina, 12);
    }

    /** A3: pagos aprobados de citas canceladas, a los que corresponde la devolución total. */
    public function devolucionesPendientes(): array
    {
        return $this->consultar(self::LISTADO . " WHERE pg.estado = 'aprobado' AND c.estado_cita_id = 4 ORDER BY c.inicio");
    }

    /** Indicadores de la caja. */
    public function resumen(): array
    {
        return $this->primero(
            "SELECT COALESCE(sum(importe) FILTER (WHERE estado = 'aprobado' AND verificado_en::date = current_date), 0) AS cobrado_hoy,
                    count(*) FILTER (WHERE estado = 'aprobado' AND verificado_en::date = current_date)                AS aprobados_hoy,
                    COALESCE(sum(importe) FILTER (WHERE estado = 'pendiente'), 0)                                   AS pendiente,
                    count(*) FILTER (WHERE estado = 'pendiente')                                                    AS pendientes
               FROM pago"
        ) ?? [];
    }

    /** A1 de CU11: si la cita tiene un pago aprobado corresponde solicitar la devolución (CU20). */
    public function tieneAprobado(int $citaId): bool
    {
        return (bool) $this->valor("SELECT 1 FROM pago WHERE cita_id = ? AND estado = 'aprobado'", [$citaId]);
    }

    /** Citas (de la lista dada) con pago aprobado, para avisar antes de cancelar. */
    public function citasConPagoAprobado(array $citaIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $citaIds)));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        return array_map('intval', array_column(
            $this->consultar("SELECT DISTINCT cita_id FROM pago WHERE estado = 'aprobado' AND cita_id IN ($marcas)", $ids),
            'cita_id'
        ));
    }

    public function contarPendientes(): int
    {
        return (int) $this->valor("SELECT count(*) FROM pago WHERE estado = 'pendiente'");
    }
}
