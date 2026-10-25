<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Personal administrativo (administradores y recepcionistas): subtipo de datos_personales. */
final class Epersonal extends Modelo
{
    protected const TABLA = 'personal';

    public function registrar(int $id, string $fechaIngreso): void
    {
        $this->ejecutar('INSERT INTO personal (id, fecha_ingreso) VALUES (?, ?) ON CONFLICT (id) DO NOTHING', [$id, $fechaIngreso]);
    }
}
