<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Citas;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica as Cita;

/** CU11 y CU12 · Transiciones de estado permitidas de una cita (RF09, RF10). */
final class TransicionEstadoTest extends TestCase
{
    public static function permitidas(): array
    {
        return [
            'confirmar una programada'          => [Cita::PROGRAMADA, Cita::CONFIRMADA],
            'cancelar una programada'           => [Cita::PROGRAMADA, Cita::CANCELADA],
            'cancelar una confirmada'           => [Cita::CONFIRMADA, Cita::CANCELADA],
            'inasistencia de una programada'    => [Cita::PROGRAMADA, Cita::INASISTENCIA],
            'inasistencia de una confirmada'    => [Cita::CONFIRMADA, Cita::INASISTENCIA],
            'atender una confirmada (CU17)'     => [Cita::CONFIRMADA, Cita::ATENDIDA],
        ];
    }

    public static function rechazadas(): array
    {
        return [
            'E3: inasistencia a confirmada'     => [Cita::INASISTENCIA, Cita::CONFIRMADA],
            'E1: cancelar una atendida'         => [Cita::ATENDIDA, Cita::CANCELADA],
            'E2: cancelar dos veces'            => [Cita::CANCELADA, Cita::CANCELADA],
            'confirmar dos veces'               => [Cita::CONFIRMADA, Cita::CONFIRMADA],
            'reactivar una cancelada'           => [Cita::CANCELADA, Cita::PROGRAMADA],
            'volver de confirmada a programada' => [Cita::CONFIRMADA, Cita::PROGRAMADA],
        ];
    }

    #[DataProvider('permitidas')]
    public function testPermiteLaTransicion(int $desde, int $hacia): void
    {
        $this->assertTrue(Cita::transicionPermitida($desde, $hacia));
    }

    #[DataProvider('rechazadas')]
    public function testRechazaLaTransicion(int $desde, int $hacia): void
    {
        $this->assertFalse(Cita::transicionPermitida($desde, $hacia));
    }
}
