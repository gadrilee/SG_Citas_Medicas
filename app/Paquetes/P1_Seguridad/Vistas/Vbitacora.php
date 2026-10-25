<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU03 · Bitácora con filtros y paginación. */
final class Vbitacora extends Vista
{
    public function mostrar(array $resultado, array $filtros, array $opciones, array $erroresFiltro): Respuesta
    {
        return $this->render('bitacora/lista', [
            'titulo'        => 'Bitácora',
            'migas'         => 'Seguridad',
            'resultado'     => $resultado,
            'filtros'       => $filtros,
            'opciones'      => $opciones,
            'erroresFiltro' => $erroresFiltro,
        ]);
    }
}
