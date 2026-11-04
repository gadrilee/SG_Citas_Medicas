<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Catálogo fijo de estados de la cita (1 programada … 5 inasistencia). Se usa en filtros y reportes. */
final class Eestado_cita extends Modelo
{
    protected const TABLA = 'estado_cita';

    public function todos(): array
    {
        return $this->consultar('SELECT id, nombre, descripcion FROM estado_cita ORDER BY id');
    }
}
