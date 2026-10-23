<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Agenda;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use VidaSalud\Paquetes\P2_Agenda\Controladores\Cdisponibilidad;

/** CU08 · Cálculo de cupos a partir de ventanas, citas ocupadas y la hora actual. */
final class GenerarTurnosTest extends TestCase
{
    private const VENTANA = ['hora_inicio' => '08:00', 'hora_fin' => '09:00', 'duracion_cita_min' => 15];

    private static function ahora(string $momento): DateTimeImmutable
    {
        return new DateTimeImmutable($momento, new DateTimeZone('America/La_Paz'));
    }

    public function testDivideLaVentanaEnTurnosCompletos(): void
    {
        $cupos = Cdisponibilidad::generarTurnos('2026-11-16', [self::VENTANA], [], self::ahora('2026-11-01 10:00'));

        $this->assertCount(4, $cupos);
        $this->assertSame('2026-11-16T08:00:00-04:00', $cupos[0]['inicio']);
        $this->assertSame('2026-11-16T08:15:00-04:00', $cupos[0]['fin']);
        $this->assertSame('2026-11-16T09:00:00-04:00', $cupos[3]['fin']);
        $this->assertTrue($cupos[0]['libre']);
    }

    public function testMarcaOcupadosLosTurnosConCitaNoCancelada(): void
    {
        $ocupadas = [['inicio' => '2026-11-16 08:15:00-04', 'fin' => '2026-11-16 08:30:00-04']];
        $cupos = Cdisponibilidad::generarTurnos('2026-11-16', [self::VENTANA], $ocupadas, self::ahora('2026-11-01 10:00'));

        $this->assertSame([true, false, true, true], array_column($cupos, 'libre'));
    }

    public function testLosTurnosQueYaPasaronNoEstanLibres(): void
    {
        $cupos = Cdisponibilidad::generarTurnos('2026-11-16', [self::VENTANA], [], self::ahora('2026-11-16 08:20'));

        $this->assertSame([false, false, true, true], array_column($cupos, 'libre'));
    }

    public function testUneVariasVentanasOrdenadasPorHora(): void
    {
        $tarde = ['hora_inicio' => '14:00', 'hora_fin' => '14:30', 'duracion_cita_min' => 30];
        $cupos = Cdisponibilidad::generarTurnos('2026-11-16', [$tarde, self::VENTANA], [], self::ahora('2026-11-01 10:00'));

        $this->assertCount(5, $cupos);
        $this->assertSame('2026-11-16T14:00:00-04:00', $cupos[4]['inicio']);
        $this->assertSame('2026-11-16T14:30:00-04:00', $cupos[4]['fin']);
    }

    public function testUnaCitaLargaOcupaTodosLosTurnosQueCruza(): void
    {
        $ocupadas = [['inicio' => '2026-11-16 08:10:00-04', 'fin' => '2026-11-16 08:40:00-04']];
        $cupos = Cdisponibilidad::generarTurnos('2026-11-16', [self::VENTANA], $ocupadas, self::ahora('2026-11-01 10:00'));

        $this->assertSame([false, false, false, true], array_column($cupos, 'libre'));
    }
}
