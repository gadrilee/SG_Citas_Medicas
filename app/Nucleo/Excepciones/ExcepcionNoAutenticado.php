<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo\Excepciones;

use RuntimeException;

/** Página interna solicitada sin sesión: se redirige al inicio de sesión. */
class ExcepcionNoAutenticado extends RuntimeException
{
}
