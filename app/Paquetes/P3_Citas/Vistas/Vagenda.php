<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU13 · Agenda diaria de un médico. */
final class Vagenda extends Vista
{
    public function mostrar(?array $medico, array $medicos, array $citas, array $conteo, array $ventanas, ?array $bloqueo, array $filtros, array $estados, array $erroresFiltro): Respuesta
    {
        return $this->render('agenda/dia', [
            'titulo'        => $medicos ? 'Agenda' : 'Mi agenda',
            'migas'         => 'Seguimiento',
            'medico'        => $medico,
            'medicos'       => $medicos,
            'citas'         => $citas,
            'conteo'        => $conteo,
            'ventanas'      => $ventanas,
            'bloqueo'       => $bloqueo,
            'filtros'       => $filtros,
            'estados'       => $estados,
            'erroresFiltro' => $erroresFiltro,
        ]);
    }
}
