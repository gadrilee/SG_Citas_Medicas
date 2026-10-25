<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU02 · Lista de cuentas con los modales de alta, edición, restablecimiento y desactivación. */
final class Vusuario extends Vista
{
    public function mostrarLista(array $resultado, array $personasSinCuenta, ?array $editar, int $usuarioActualId): Respuesta
    {
        return $this->render('usuarios/lista', [
            'titulo'            => 'Usuarios',
            'migas'             => 'Seguridad',
            'resultado'         => $resultado,
            'personasSinCuenta' => $personasSinCuenta,
            'editar'            => $editar,
            'usuarioActualId'   => $usuarioActualId,
            'perfiles'          => [1 => 'Administrador', 2 => 'Recepcionista', 3 => 'Médico', 4 => 'Paciente'],
        ]);
    }
}
