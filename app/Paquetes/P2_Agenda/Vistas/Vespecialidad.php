<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU06 · Catálogo de especialidades con modales de alta y edición. */
final class Vespecialidad extends Vista
{
    public function mostrarLista(array $especialidades, ?array $editar): Respuesta
    {
        return $this->render('especialidades/lista', [
            'titulo'         => 'Especialidades',
            'migas'          => 'Clínica',
            'especialidades' => $especialidades,
            'editar'         => $editar,
        ]);
    }
}
