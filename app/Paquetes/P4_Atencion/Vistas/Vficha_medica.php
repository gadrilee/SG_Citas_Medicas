<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;

/** CU16 · Historia clínica: edición (médico y recepción) o solo lectura (paciente). */
final class Vficha_medica extends Vista
{
    public function mostrar(array $paciente, ?array $ficha, array $consultas, bool $editable, bool $propia): Respuesta
    {
        return $this->render('historia/ficha', [
            'titulo'      => $propia ? 'Mi historia clínica' : 'Historia clínica',
            'migas'       => $propia ? 'Mi salud' : 'Pacientes',
            'paciente'    => $paciente,
            'ficha'       => $ficha ?? [],
            'consultas'   => $consultas,
            'editable'    => $editable,
            'propia'      => $propia,
            'tiposSangre' => Eficha_medica::TIPOS_SANGRE,
            'campos'      => Eficha_medica::CAMPOS_TEXTO,
        ]);
    }
}
