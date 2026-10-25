<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;

/** CU04 · Pantallas de pacientes: lista, formulario y ficha. */
final class Vpaciente extends Vista
{
    public function mostrarLista(array $resultado): Respuesta
    {
        return $this->render('pacientes/lista', ['titulo' => 'Pacientes', 'migas' => 'Personas', 'resultado' => $resultado]);
    }

    /** @param array|null $paciente null = registro nuevo */
    public function mostrarFormulario(?array $paciente, array $extra): Respuesta
    {
        return $this->render('pacientes/formulario', [
            'titulo'       => $paciente ? 'Editar paciente' : 'Registrar paciente',
            'migas'        => 'Pacientes',
            'paciente'     => $paciente,
            'tiposSangre'  => Eficha_medica::TIPOS_SANGRE,
        ] + $extra);
    }

    public function mostrarFicha(array $paciente, array $contactos, ?array $ficha, array $citas, array $pagos): Respuesta
    {
        return $this->render('pacientes/ficha', [
            'titulo'    => 'Ficha del paciente',
            'migas'     => 'Pacientes',
            'paciente'  => $paciente,
            'contactos' => $contactos,
            'ficha'     => $ficha,
            'citas'     => $citas,
            'pagos'     => $pagos,
        ]);
    }
}
