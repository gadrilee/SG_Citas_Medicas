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

    public function contarPendientes(): int
    {
        return (int) $this->valor("SELECT count(*) FROM pago WHERE estado = 'pendiente'");
    }
}
