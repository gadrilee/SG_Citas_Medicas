<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU09 y CU10 · Reserva, comprobante y reprogramación de citas. */
final class Vcita extends Vista
{
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

    public function mostrarComprobante(array $cita, array $historial): Respuesta
    {
        return $this->render('citas/comprobante', [
            'titulo'    => 'Comprobante ' . $cita['codigo'],
            'migas'     => 'Citas',
            'cita'      => $cita,
            'historial' => $historial,
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
