<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Controladores;

use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eestudio_laboratorio;

/**
 * CU18 · Solicitar estudios de laboratorio. Es un «extend» de CU17: no tiene rutas propias. Cconsulta
 * lo invoca en el punto de extensión «solicitar estudios» cuando el médico agregó estudios al formulario.
 */
final class Cestudio extends Controlador
{
    /**
     * Pasos 1–3: estudios agregados (estudios[i][nombre_estudio|indicaciones]).
     * A1: una fila quitada o vacía no se registra. E1: sin nombre no se agrega.
     * Devuelve [estudios, errores].
     */
    public function estudiosDelFormulario(): array
    {
        $estudios = [];
        $errores = [];
        foreach ((array) $this->solicitud->entrada('estudios', []) as $i => $fila) {
            $fila = array_map(static fn ($valor): string => trim((string) $valor), (array) $fila + ['nombre_estudio' => '', 'indicaciones' => '']);
            if ($fila['nombre_estudio'] === '' && $fila['indicaciones'] === '') {
                continue;
            }
            foreach (Validador::validar($fila, ['nombre_estudio' => 'requerido|max:150', 'indicaciones' => 'opcional|max:1000']) as $campo => $mensaje) {
                $errores["estudios[$i][$campo]"] = $mensaje;
            }
            $estudios[] = $fila;
        }
        return [$estudios, $errores];
    }

    /** Paso 4: cada estudio queda pendiente y vinculado a la consulta. Corre en la transacción de CU17 (E2). */
    public function registrar(int $consultaId, array $estudios): void
    {
        $modelo = new Eestudio_laboratorio();
        foreach ($estudios as $estudio) {
            $modelo->registrar($consultaId, $estudio['nombre_estudio'], $estudio['indicaciones']);
        }
    }
}
