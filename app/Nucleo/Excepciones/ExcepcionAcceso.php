<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo\Excepciones;

use RuntimeException;

/** Usuario autenticado sin privilegio para la operación (HTTP 403). */
class ExcepcionAcceso extends RuntimeException
{
}
