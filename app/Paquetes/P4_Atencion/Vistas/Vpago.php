<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU20 · Caja, pago de una cita (con el formulario de la pasarela embebido) y comprobante. */
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

    /**
     * @param array|null $pendiente   intento pendiente de la cita (Epago::buscarDetalle)
     * @param array|null $formulario  PasarelaPago::formulario del intento pendiente
     */
    public function mostrarPagar(array $cita, array $intentos, ?string $motivoNoPagable, ?array $pasarela, ?array $pendiente, ?array $formulario): Respuesta
    {
        return $this->render('pagos/pagar', [
            'titulo'          => 'Pagar ' . $cita['codigo'],
            'migas'           => 'Pagos',
            'cita'            => $cita,
            'intentos'        => $intentos,
            'motivoNoPagable' => $motivoNoPagable,
            'pasarela'        => $pasarela,
            'pendiente'       => $pendiente,
            'formulario'      => $formulario,
        ]);
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
