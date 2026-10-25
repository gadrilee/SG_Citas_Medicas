<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo\Excepciones;

use RuntimeException;

/** Ruta o registro inexistente (HTTP 404). */
class ExcepcionNoEncontrado extends RuntimeException
{
}
