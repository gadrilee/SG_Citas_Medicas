<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Atencion;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eestudio_laboratorio as Estudio;

/** CU19 · Estados de un estudio: pendiente → en proceso → disponible; publicado no retrocede (E4). */
final class EstudioTransicionTest extends TestCase
{
    public static function permitidas(): array
    {
        return [
            'marcar en proceso un pendiente (A1)' => [Estudio::PENDIENTE, Estudio::EN_PROCESO],
            'publicar un pendiente'               => [Estudio::PENDIENTE, Estudio::DISPONIBLE],
            'publicar uno en proceso'             => [Estudio::EN_PROCESO, Estudio::DISPONIBLE],
        ];
    }

    public static function rechazadas(): array
    {
        return [
            'E4: publicado vuelve a pendiente'  => [Estudio::DISPONIBLE, Estudio::PENDIENTE],
            'E4: publicado vuelve a en proceso' => [Estudio::DISPONIBLE, Estudio::EN_PROCESO],
            'publicar dos veces'                => [Estudio::DISPONIBLE, Estudio::DISPONIBLE],
            'en proceso vuelve a pendiente'     => [Estudio::EN_PROCESO, Estudio::PENDIENTE],
        ];
    }

    #[DataProvider('permitidas')]
    public function testPermiteLaTransicion(string $desde, string $hacia): void
    {
        $this->assertTrue(Estudio::transicionPermitida($desde, $hacia));
    }

    #[DataProvider('rechazadas')]
    public function testRechazaLaTransicion(string $desde, string $hacia): void
    {
        $this->assertFalse(Estudio::transicionPermitida($desde, $hacia));
    }
}
