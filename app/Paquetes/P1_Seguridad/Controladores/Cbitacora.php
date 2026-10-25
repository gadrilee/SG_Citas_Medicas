<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Controladores;

use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Ebitacora;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vbitacora;

/** CU03 · Consultar bitácora (solo lectura). */
final class Cbitacora extends Controlador
{
    public function listar(): Respuesta
    {
        $filtros = $this->solicitud->solo(['desde', 'hasta', 'usuario_id', 'accion', 'entidad']);
        $errores = Validador::validar($filtros, ['desde' => 'opcional|fecha', 'hasta' => 'opcional|fecha', 'usuario_id' => 'opcional|entero']);
        if (!$errores && !empty($filtros['desde']) && !empty($filtros['hasta']) && $filtros['hasta'] < $filtros['desde']) {
            $errores['hasta'] = 'La fecha final no puede ser anterior a la inicial.';
        }

        $bitacora = new Ebitacora();
        $resultado = $errores
            ? ['filas' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1, 'desde' => 0, 'hasta' => 0]
            : $bitacora->listar($filtros, (int) $this->solicitud->consulta('pagina', 1));

        return (new Vbitacora())->mostrar($resultado, $filtros, $bitacora->opcionesDeFiltro(), $errores);
    }
}
