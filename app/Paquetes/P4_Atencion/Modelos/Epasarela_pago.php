<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Catálogo de pasarelas de pago (simulada, stripe). */
final class Epasarela_pago extends Modelo
{
    protected const TABLA = 'pasarela_pago';

    public function activaPorCodigo(string $codigo): ?array
    {
        return $this->primero('SELECT * FROM pasarela_pago WHERE codigo = ? AND activo', [$codigo]);
    }
}
