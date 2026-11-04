<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU19 · Estudios del médico (publicación) y «Mis resultados» del paciente. */
final class Vresultado extends Vista
{
    public function mostrarEstudios(array $resultado, array $conteo, array $filtros): Respuesta
    {
        return $this->render('estudios/lista', [
            'titulo'    => 'Estudios',
            'migas'     => 'Atención',
            'resultado' => $resultado,
            'conteo'    => $conteo,
            'filtros'   => $filtros,
        ]);
    }

    /** Informe formal imprimible y descargable en PDF. */
    public function mostrarInforme(array $estudio): Respuesta
    {
        return $this->render('resultados/informe', [
            'titulo'  => 'Informe LAB-' . str_pad((string) $estudio['id'], 6, '0', STR_PAD_LEFT),
            'migas'   => 'Mis resultados',
            'estudio' => $estudio,
        ]);
    }

    public function mostrarMisResultados(array $estudios): Respuesta
    {
        return $this->render('resultados/mis_resultados', [
            'titulo'   => 'Mis resultados',
            'migas'    => 'Mi salud',
            'estudios' => $estudios,
        ]);
    }
}
