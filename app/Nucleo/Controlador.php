<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;

/**
 * Base de los controladores (C*). Un controlador coordina el flujo del caso de uso: valida la
 * entrada, aplica reglas, usa los modelos (E*) y devuelve la respuesta mediante su vista (V*).
 */
abstract class Controlador
{
    public function __construct(protected Solicitud $solicitud)
    {
    }

    /** Valida la entrada; si falla, lanza ExcepcionValidacion y la aplicación vuelve al formulario. */
    protected function validar(array $reglas): array
    {
        $datos = $this->solicitud->todo();
        $errores = Validador::validar($datos, $reglas);
        if ($errores) {
            throw new ExcepcionValidacion($errores, $datos);
        }
        return array_intersect_key($datos, $reglas);
    }

    protected function redirigir(string $ruta, ?string $tipo = null, ?string $mensaje = null): Respuesta
    {
        if ($tipo && $mensaje) {
            Sesion::flash($tipo, $mensaje);
        }
        return Respuesta::redirigir($ruta);
    }

    protected function volver(?string $tipo = null, ?string $mensaje = null): Respuesta
    {
        return $this->redirigir($this->solicitud->anterior(), $tipo, $mensaje);
    }

    protected function json(array $datos, int $codigo = 200): Respuesta
    {
        return Respuesta::json($datos, $codigo);
    }

    protected function usuario(): ?array
    {
        return Sesion::usuario();
    }
}
