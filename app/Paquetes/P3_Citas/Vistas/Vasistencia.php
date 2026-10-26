<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU12 · Control de asistencia del día, agrupado por médico. */
final class Vasistencia extends Vista
{
    public function mostrar(array $porMedico, array $conteo, array $filtros, array $medicos, array $estados, array $erroresFiltro): Respuesta
    {
        return $this->render('asistencia/dia', [
            'titulo'        => 'Control de asistencia',
            'migas'         => 'Citas',
            'porMedico'     => $porMedico,
            'conteo'        => $conteo,
            'filtros'       => $filtros,
            'medicos'       => $medicos,
            'estados'       => $estados,
            'erroresFiltro' => $erroresFiltro,
        ]);
    }
}
