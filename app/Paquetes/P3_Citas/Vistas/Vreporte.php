<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU15 · Reportes administrativos con filtros, totales y versión imprimible. */
final class Vreporte extends Vista
{
    public function mostrar(string $tipo, array $tipos, array $filtros, array $reporte, array $opciones, array $erroresFiltro): Respuesta
    {
        return $this->render('reportes/reporte', [
            'titulo'        => 'Reporte · ' . $tipos[$tipo][0],
            'migas'         => 'Reportes',
            'tipo'          => $tipo,
            'tipos'         => $tipos,
            'filtros'       => $filtros,
            'reporte'       => $reporte,
            'opciones'      => $opciones,
            'erroresFiltro' => $erroresFiltro,
        ]);
    }
}
