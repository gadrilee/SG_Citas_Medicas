<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Nucleo;

use PHPUnit\Framework\TestCase;
use VidaSalud\Nucleo\Validador;

final class ValidadorTest extends TestCase
{
    public function testExigeLosCamposRequeridos(): void
    {
        $errores = Validador::validar(['nombres' => '  '], ['nombres' => 'requerido', 'ci' => 'requerido']);

        $this->assertSame(['nombres', 'ci'], array_keys($errores));
    }

    public function testLosCamposOpcionalesVaciosNoSeValidan(): void
    {
        $this->assertSame([], Validador::validar(['correo' => ''], ['correo' => 'opcional|correo']));
    }

    public function testRechazaFechaDeNacimientoFutura(): void
    {
        $manana = date('Y-m-d', strtotime('+1 day'));
        $errores = Validador::validar(['fecha_nacimiento' => $manana], ['fecha_nacimiento' => 'requerido|fecha|fecha_no_futura']);

        $this->assertArrayHasKey('fecha_nacimiento', $errores);
    }

    public function testRechazaFechasInexistentes(): void
    {
        $errores = Validador::validar(['fecha' => '2026-02-30'], ['fecha' => 'fecha']);

        $this->assertArrayHasKey('fecha', $errores);
    }

    public function testComparaLaConfirmacionDeContrasena(): void
    {
        $datos = ['contrasena' => 'VidaSalud2026', 'contrasena_confirmacion' => 'otra'];
        $errores = Validador::validar($datos, ['contrasena_confirmacion' => 'requerido|coincide:contrasena']);

        $this->assertSame('Los valores no coinciden.', $errores['contrasena_confirmacion']);
    }

    public function testNombreDeUsuarioSinArroba(): void
    {
        $errores = Validador::validar(['nombre_usuario' => 'rosa@ag'], ['nombre_usuario' => 'requerido|sin_arroba|max:50']);

        $this->assertArrayHasKey('nombre_usuario', $errores);
    }
}
