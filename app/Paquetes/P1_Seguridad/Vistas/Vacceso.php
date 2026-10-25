<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU01 · Pantallas de acceso: iniciar sesión y registro web del paciente. */
final class Vacceso extends Vista
{
    protected string $layout = 'simple';

    public function mostrarLogin(): Respuesta
    {
        return $this->render('acceso/iniciar_sesion', [
            'titulo'     => 'Iniciar sesión',
            'clase_main' => 'min-h-screen flex items-center',
        ]);
    }

    public function mostrarRegistro(): Respuesta
    {
        return $this->render('acceso/registro', ['titulo' => 'Crear cuenta']);
    }
}
