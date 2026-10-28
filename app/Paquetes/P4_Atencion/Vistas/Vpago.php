<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU20 · Caja, pago de una cita, formulario de la pasarela simulada y comprobante. */
final class Vpago extends Vista
{
    public function mostrarLista(array $resultado, array $resumen, array $devoluciones, array $filtros): Respuesta
    {
        return $this->render('pagos/lista', [
            'titulo'       => 'Pagos',
            'migas'        => 'Caja',
            'resultado'    => $resultado,
            'resumen'      => $resumen,
            'devoluciones' => $devoluciones,
            'filtros'      => $filtros,
        ]);
    }

    public function mostrarPagar(array $cita, array $intentos, ?string $motivoNoPagable, ?array $pasarela): Respuesta
    {
        return $this->render('pagos/pagar', [
            'titulo'          => 'Pagar ' . $cita['codigo'],
            'migas'           => 'Pagos',
            'cita'            => $cita,
            'intentos'        => $intentos,
            'motivoNoPagable' => $motivoNoPagable,
            'pasarela'        => $pasarela,
        ]);
    }

    /** Pantalla sin menú: representa el sitio externo de la pasarela. */
    public function mostrarSimulada(array $pago): Respuesta
    {
        return $this->render('pagos/simulada', ['titulo' => 'Vida Pay · Pago seguro', 'pago' => $pago, 'clase_main' => 'min-h-screen flex items-center justify-center p-4'], 'simple');
    }

    public function mostrarComprobante(array $pago, array $cita): Respuesta
    {
        return $this->render('pagos/comprobante', [
            'titulo' => 'Comprobante de pago',
            'migas'  => 'Pagos',
            'pago'   => $pago,
            'cita'   => $cita,
        ]);
    }
}
