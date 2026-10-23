<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU01 · Mi cuenta: datos, contraseña y preferencias. */
final class Vcuenta extends Vista
{
    public function mostrar(array $usuario, array $persona, array $preferencias, string $pestana): Respuesta
    {
        return $this->render('cuenta/mi_cuenta', [
            'titulo'       => 'Mi cuenta',
            'migas'        => 'Cuenta',
            'usuario'      => $usuario,
            'persona'      => $persona,
            'preferencias' => $preferencias,
            'pestana'      => in_array($pestana, ['datos', 'contrasena', 'preferencias'], true) ? $pestana : 'datos',
        ]);
    }
}
