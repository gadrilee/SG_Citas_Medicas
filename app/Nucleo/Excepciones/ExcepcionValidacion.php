<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo\Excepciones;

use RuntimeException;

/** Datos de entrada inválidos: vuelve al formulario con los errores por campo (HTTP 422 en la API). */
class ExcepcionValidacion extends RuntimeException
{
    public function __construct(private array $errores, private array $entradas = [])
    {
        parent::__construct('Revisa los datos del formulario.');
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function entradas(): array
    {
        return $this->entradas;
    }
}
