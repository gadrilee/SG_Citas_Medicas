<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;

/**
 * Asocia método + ruta con un controlador. Cada paquete declara sus rutas en rutas.php:
 *
 *   ['POST', '/citas/{id}/cancelar', [Ccita::class, 'cancelar'], 'citas.cancelar']
 *   ['POST', '/pagos/webhook', [Cpago::class, 'webhook'], null, ['csrf' => false]]
 *
 * El cuarto valor es el privilegio exigido (null = pública, 'autenticado' = cualquier sesión).
 */
final class Enrutador
{
    private array $rutas = [];

    public function agregar(string $metodo, string $patron, array $accion, ?string $privilegio = null, array $opciones = []): void
    {
        $this->rutas[] = [
            'metodo'     => strtoupper($metodo),
            'patron'     => $patron,
            'expresion'  => self::compilar($patron),
            'accion'     => $accion,
            'privilegio' => $privilegio,
            'opciones'   => $opciones + ['csrf' => true],
        ];
    }

    public function cargarArchivo(string $archivo): void
    {
        foreach (require $archivo as $ruta) {
            $this->agregar(...$ruta);
        }
    }

    public function despachar(Solicitud $solicitud): Respuesta
    {
        $metodoPermitido = false;
        foreach ($this->rutas as $ruta) {
            if (!preg_match($ruta['expresion'], $solicitud->ruta(), $coincidencias)) {
                continue;
            }
            $metodoPermitido = true;
            if ($ruta['metodo'] !== $solicitud->metodo()) {
                continue;
            }

            Autorizacion::exigir($ruta['privilegio']);
            if ($solicitud->metodo() !== 'GET' && $ruta['opciones']['csrf']) {
                Csrf::validar($solicitud);
            }

            $parametros = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            [$clase, $metodo] = $ruta['accion'];
            return (new $clase($solicitud))->$metodo(...array_values($parametros));
        }

        if ($metodoPermitido) {
            return Respuesta::html('Método no permitido', 405);
        }
        throw new ExcepcionNoEncontrado('La página solicitada no existe.');
    }

    /** '/citas/{id}/cancelar' → '#^/citas/(?P<id>[^/]+)/cancelar$#' */
    private static function compilar(string $patron): string
    {
        $expresion = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $patron);
        return '#^' . $expresion . '$#u';
    }
}
