<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU09, CU10 y CU11 · Reserva, comprobante, reprogramación, búsqueda y cancelación de citas. */
final class Vcita extends Vista
{
    public function mostrarLista(array $resultado, array $filtros, array $medicos, array $estados, array $conPagoAprobado, array $erroresFiltro): Respuesta
    {
        return $this->render('citas/lista', [
            'titulo'          => 'Citas',
            'migas'           => 'Citas',
            'resultado'       => $resultado,
            'filtros'         => $filtros,
            'medicos'         => $medicos,
            'estados'         => $estados,
            'conPagoAprobado' => $conPagoAprobado,
            'erroresFiltro'   => $erroresFiltro,
        ]);
    }

    public function mostrarReserva(array $especialidades, array $medicos, array $pacientes, ?array $paciente, array $seleccion): Respuesta
    {
        return $this->render('citas/reservar', [
            'titulo'         => 'Nueva cita',
            'migas'          => 'Citas',
            'especialidades' => $especialidades,
            'medicos'        => $medicos,
            'pacientes'      => $pacientes,
            'paciente'       => $paciente,
            'seleccion'      => $seleccion,
        ]);
    }

    public function mostrarComprobante(array $cita, array $historial, bool $pagoAprobado): Respuesta
    {
        return $this->render('citas/comprobante', [
            'titulo'       => 'Comprobante ' . $cita['codigo'],
            'migas'        => 'Citas',
            'cita'         => $cita,
            'historial'    => $historial,
            'pagoAprobado' => $pagoAprobado,
        ]);
    }

    public function mostrarReprogramar(array $cita, array $diasAtencion, array $bloqueos): Respuesta
    {
        return $this->render('citas/reprogramar', [
            'titulo'       => 'Reprogramar ' . $cita['codigo'],
            'migas'        => 'Citas / ' . $cita['codigo'],
            'cita'         => $cita,
            'diasAtencion' => $diasAtencion,
            'bloqueos'     => $bloqueos,
        ]);
    }
}
