<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU07 · Semana de atención del médico, bloqueos y confirmación de días con citas. */
final class Vhorario extends Vista
{
    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public function mostrar(array $medicos, ?array $medico, array $ventanas, array $bloqueos, ?array $editar, ?array $pendiente, array $afectadas): Respuesta
    {
        $porDia = array_fill_keys(array_keys(self::DIAS), []);
        foreach ($ventanas as $ventana) {
            $porDia[(int) $ventana['dia_semana']][] = $ventana;
        }
        return $this->render('horarios/semana', [
            'titulo'    => 'Horarios de atención',
            'migas'     => 'Clínica',
            'medicos'   => $medicos,
            'medico'    => $medico,
            'porDia'    => $porDia,
            'dias'      => self::DIAS,
            'bloqueos'  => $bloqueos,
            'editar'    => $editar,
            'pendiente' => $pendiente,
            'afectadas' => $afectadas,
        ]);
    }
}
