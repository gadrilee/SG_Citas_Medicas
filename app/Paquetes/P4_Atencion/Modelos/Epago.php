<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Intentos de pago de una cita (dueño: CU20). En P1 solo se consultan. */
final class Epago extends Modelo
{
    protected const TABLA = 'pago';

    public function dePaciente(int $pacienteId): array
    {
        return $this->consultar(
            'SELECT pg.creado_en, pg.importe, pg.moneda, pg.estado, pg.referencia_externa, c.codigo AS cita, pp.nombre AS pasarela
               FROM pago pg JOIN cita_medica c ON c.id = pg.cita_id JOIN pasarela_pago pp ON pp.id = pg.pasarela_id
              WHERE c.paciente_id = ? ORDER BY pg.creado_en DESC',
            [$pacienteId]
        );
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
