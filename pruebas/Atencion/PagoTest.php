<?php

declare(strict_types=1);

namespace VidaSalud\Pruebas\Atencion;

use PHPUnit\Framework\TestCase;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epago;
use VidaSalud\Paquetes\P4_Atencion\Pasarelas\PasarelaSimulada;
use VidaSalud\Paquetes\P4_Atencion\Pasarelas\PasarelaStripe;

/** CU20 · Verificación de la respuesta de la pasarela antes de cambiar el estado del pago. */
final class PagoTest extends TestCase
{
    private const PAGO = ['id' => 7, 'referencia_externa' => 'VP-ABC123', 'importe' => '150.00', 'moneda' => 'BOB'];

    // ---------------------------------------------------------------- E1: importe y moneda

    public function testApruebaSiImporteYMonedaCoinciden(): void
    {
        $this->assertSame('aprobado', Epago::estadoVerificado(self::PAGO, ['estado' => 'aprobado', 'importe' => '150.00', 'moneda' => 'BOB']));
    }

    public function testE1NoApruebaSiElImporteEsDistinto(): void
    {
        $this->assertSame('rechazado', Epago::estadoVerificado(self::PAGO, ['estado' => 'aprobado', 'importe' => '1.50', 'moneda' => 'BOB']));
    }

    public function testE1NoApruebaSiLaMonedaEsDistinta(): void
    {
        $this->assertSame('rechazado', Epago::estadoVerificado(self::PAGO, ['estado' => 'aprobado', 'importe' => '150.00', 'moneda' => 'USD']));
    }

    public function testConservaUnRechazoOUnaCancelacion(): void
    {
        $this->assertSame('rechazado', Epago::estadoVerificado(self::PAGO, ['estado' => 'rechazado']));
        $this->assertSame('cancelado', Epago::estadoVerificado(self::PAGO, ['estado' => 'cancelado']));
    }

    // ---------------------------------------------------------------- pasarela simulada

    public function testTarjeta4242SeApruebaYSuRespuestaFirmadaSeVerifica(): void
    {
        $pasarela = new PasarelaSimulada('clave-de-prueba');
        $respuesta = $pasarela->cobrar(self::PAGO, '4242 4242 4242 4242');
        $this->assertSame('aprobado', $respuesta['estado']);
        $this->assertSame('aprobado', $pasarela->verificar(self::PAGO, $respuesta)['estado']);
    }

    public function testTarjeta0002SeRechaza(): void
    {
        $pasarela = new PasarelaSimulada('clave-de-prueba');
        $this->assertSame('rechazado', $pasarela->cobrar(self::PAGO, '4000 0000 0000 0002')['estado']);
    }

    public function testRespuestaAlteradaEnLaUrlNoSeAcepta(): void
    {
        $pasarela = new PasarelaSimulada('clave-de-prueba');
        $respuesta = $pasarela->cobrar(self::PAGO, '4000 0000 0000 0002');
        $respuesta['estado'] = 'aprobado';
        $this->expectException(ExcepcionNegocio::class);
        $pasarela->verificar(self::PAGO, $respuesta);
    }

    public function testRespuestaDeOtroIntentoNoSeAcepta(): void
    {
        $pasarela = new PasarelaSimulada('clave-de-prueba');
        $respuesta = $pasarela->cobrar(['referencia_externa' => 'VP-OTRO'] + self::PAGO, '4242424242424242');
        $this->expectException(ExcepcionNegocio::class);
        $pasarela->verificar(self::PAGO, $respuesta);
    }

    public function testA2SinRespuestaQuedaPendiente(): void
    {
        $this->assertSame('pendiente', (new PasarelaSimulada('clave-de-prueba'))->verificar(self::PAGO, [])['estado']);
    }

    // ---------------------------------------------------------------- webhook de Stripe

    private static function firmaStripe(string $cuerpo, int $marca, string $secreto): string
    {
        return 't=' . $marca . ',v1=' . hash_hmac('sha256', $marca . '.' . $cuerpo, $secreto);
    }

    public function testWebhookConFirmaValidaSeAcepta(): void
    {
        $cuerpo = '{"type":"checkout.session.completed","data":{"object":{"id":"cs_test_123"}}}';
        $ahora = 1_800_000_000;
        $evento = (new PasarelaStripe('sk_test_x', 'whsec_prueba'))->leerWebhook($cuerpo, self::firmaStripe($cuerpo, $ahora, 'whsec_prueba'), $ahora);
        $this->assertSame('cs_test_123', $evento['data']['object']['id']);
    }

    public function testWebhookConCuerpoAlteradoSeRechaza(): void
    {
        $cuerpo = '{"type":"checkout.session.completed"}';
        $ahora = 1_800_000_000;
        $this->expectException(ExcepcionNegocio::class);
        (new PasarelaStripe('sk_test_x', 'whsec_prueba'))->leerWebhook($cuerpo . ' ', self::firmaStripe($cuerpo, $ahora, 'whsec_prueba'), $ahora);
    }

    public function testWebhookFirmadoConOtroSecretoSeRechaza(): void
    {
        $cuerpo = '{"type":"checkout.session.completed"}';
        $ahora = 1_800_000_000;
        $this->expectException(ExcepcionNegocio::class);
        (new PasarelaStripe('sk_test_x', 'whsec_prueba'))->leerWebhook($cuerpo, self::firmaStripe($cuerpo, $ahora, 'whsec_otro'), $ahora);
    }

    public function testWebhookAntiguoSeRechaza(): void
    {
        $cuerpo = '{"type":"checkout.session.completed"}';
        $marca = 1_800_000_000;
        $this->expectException(ExcepcionNegocio::class);
        (new PasarelaStripe('sk_test_x', 'whsec_prueba'))->leerWebhook($cuerpo, self::firmaStripe($cuerpo, $marca, 'whsec_prueba'), $marca + 3600);
    }
}
