<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU14 · Historial de citas: búsqueda del paciente, sus citas con eventos y «Mis citas». */
final class Vhistorial extends Vista
{
    public function mostrarBusqueda(string $buscar, array $resultado): Respuesta
    {
        return $this->render('historial/buscar', [
            'titulo'    => 'Historial de citas',
            'migas'     => 'Seguimiento',
            'buscar'    => $buscar,
            'resultado' => $resultado,
        ]);
    }

    public function mostrarPaciente(array $paciente, array $citas, array $eventos, array $filtros, array $estados, array $erroresFiltro): Respuesta
    {
        return $this->render('historial/paciente', [
            'titulo'        => 'Historial · ' . $paciente['nombres'] . ' ' . $paciente['apellidos'],
            'migas'         => 'Historial de citas',
            'paciente'      => $paciente,
            'citas'         => $citas,
            'eventos'       => $eventos,
            'filtros'       => $filtros,
            'estados'       => $estados,
            'erroresFiltro' => $erroresFiltro,
        ]);
    }

    public function mostrarMisCitas(array $citas, array $eventos, array $conPagoAprobado, array $filtros, array $estados, array $erroresFiltro): Respuesta
    {
        return $this->render('historial/mis_citas', [
            'titulo'          => 'Mis citas',
            'migas'           => 'Mis citas',
            'citas'           => $citas,
            'eventos'         => $eventos,
            'conPagoAprobado' => $conPagoAprobado,
            'filtros'         => $filtros,
            'estados'         => $estados,
            'erroresFiltro'   => $erroresFiltro,
        ]);
    }
}
