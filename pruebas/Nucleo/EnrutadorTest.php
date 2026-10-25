<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Nucleo;

use PHPUnit\Framework\TestCase;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Enrutador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoAutenticado;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Solicitud;

final class ControladorDePrueba extends Controlador
{
    public function mostrar(string $id): Respuesta
    {
        return Respuesta::json(['id' => $id, 'metodo' => $this->solicitud->metodo()]);
    }
}

final class EnrutadorTest extends TestCase
{
    private function enrutador(): Enrutador
    {
        $enrutador = new Enrutador();
        $enrutador->agregar('GET', '/citas/{id}', [ControladorDePrueba::class, 'mostrar'], null);
        $enrutador->agregar('GET', '/privada/{id}', [ControladorDePrueba::class, 'mostrar'], 'citas.reservar');
        return $enrutador;
    }

    public function testExtraeLosParametrosDeLaRuta(): void
    {
        $respuesta = $this->enrutador()->despachar(new Solicitud('GET', '/citas/42'));

        $this->assertSame('{"id":"42","metodo":"GET"}', $respuesta->contenido());
    }

    public function testRutaInexistenteLanza404(): void
    {
        $this->expectException(ExcepcionNoEncontrado::class);
        $this->enrutador()->despachar(new Solicitud('GET', '/no-existe'));
    }

    public function testMetodoNoPermitidoDevuelve405(): void
    {
        $respuesta = $this->enrutador()->despachar(new Solicitud('DELETE', '/citas/42'));

        $this->assertSame(405, $respuesta->codigo());
    }

    public function testRutaConPrivilegioExigeSesion(): void
    {
        $this->expectException(ExcepcionNoAutenticado::class);
        $this->enrutador()->despachar(new Solicitud('GET', '/privada/1'));
    }

    public function testInterpretaLaUrlDeLaBaseDeDatos(): void
    {
        [$dsn, $usuario, $clave] = BaseDatos::interpretarUrl('postgres://vida:cl%40ve@ep-ejemplo.neon.tech/vida_salud?sslmode=require');

        $this->assertSame('pgsql:host=ep-ejemplo.neon.tech;port=5432;dbname=vida_salud;sslmode=require', $dsn);
        $this->assertSame(['vida', 'cl@ve'], [$usuario, $clave]);
    }
}
