<?php

declare(strict_types=1);

namespace VidaSalud\Compartido\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

final class Vinicio extends Vista
{
    public function mostrarInicio(array $especialidades, array $medicos): Respuesta
    {
        return $this->render('inicio', [
            'titulo'         => 'Inicio',
            'especialidades' => $especialidades,
            'medicos'        => $medicos,
        ], 'publico');
    }

    /** Panel de inicio: una plantilla por perfil (panel/administrador.php, …). */
    public function mostrarPanel(array $usuario, array $datos): Respuesta
    {
        $migas = [
            'administrador' => 'Panel de administración',
            'recepcionista' => 'Panel de recepción',
            'medico'        => 'Panel médico',
            'paciente'      => 'Mi panel',
        ];
        return $this->render('panel/' . $usuario['perfil'], [
            'titulo'  => 'Inicio',
            'migas'   => $migas[$usuario['perfil']],
            'usuario' => $usuario,
            'saludo'  => self::saludo(),
        ] + $datos);
    }

    private static function saludo(): string
    {
        $hora = (int) date('G');
        return $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    }
}
