<?php

declare(strict_types=1);

namespace VidaSalud\Compartido\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

final class Verror extends Vista
{
    protected string $layout = 'simple';

    private const TITULOS = [
        403 => 'No tienes permiso para ver esta página',
        404 => 'No encontramos esta página',
        500 => 'Algo salió mal',
    ];

    public function mostrar(int $codigo, string $mensaje, ?string $detalle = null): Respuesta
    {
        return $this->render('errores/error', [
            'titulo'     => self::TITULOS[$codigo] ?? 'Error',
            'codigo'     => $codigo,
            'mensaje'    => $mensaje,
            'detalle'    => $detalle,
            'clase_main' => 'min-h-screen flex items-center justify-center p-4',
        ], null, $codigo);
    }
}
