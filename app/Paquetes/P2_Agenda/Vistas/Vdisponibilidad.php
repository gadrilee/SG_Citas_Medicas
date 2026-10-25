<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/**
 * CU08 · La disponibilidad se muestra con el calendario y los chips de publico/js/cupos.js;
 * esta vista entrega los cupos como JSON a ese componente.
 */
final class Vdisponibilidad extends Vista
{
    public function responder(string $fecha, array $resultado, array $sugerencias): Respuesta
    {
        return Respuesta::json([
            'fecha'       => $fecha,
            'cupos'       => $resultado['cupos'],
            'motivo'      => $resultado['motivo'],
            'sugerencias' => $sugerencias,
        ]);
    }
}
