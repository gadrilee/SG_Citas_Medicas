<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo\Excepciones;

use RuntimeException;

/** Regla de negocio incumplida (ej.: "El horario seleccionado ya no está disponible"). Se muestra al usuario. */
class ExcepcionNegocio extends RuntimeException
{
}
