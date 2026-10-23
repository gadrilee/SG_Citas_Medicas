<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU02 · Matriz de privilegios por perfil, agrupada por paquete. */
final class Vperfil extends Vista
{
    private const PAQUETES = [
        'P1 · Seguridad y personas'        => [1, 5],
        'P2 · Agenda y reservas'           => [6, 10],
        'P3 · Seguimiento de citas'        => [11, 15],
        'P4 · Atención clínica y pagos'    => [16, 20],
    ];

    private const ICONOS = ['administrador' => 'shield', 'recepcionista' => 'headset', 'medico' => 'stethoscope', 'paciente' => 'user-round'];

    public function mostrar(array $perfiles, array $perfil, array $privilegios, array $fijosAdmin): Respuesta
    {
        return $this->render('perfiles/privilegios', [
            'titulo'     => 'Perfiles y privilegios',
            'migas'      => 'Seguridad',
            'perfiles'   => $perfiles,
            'perfil'     => $perfil,
            'grupos'     => self::agrupar($privilegios),
            'fijos'      => $perfil['nombre'] === 'administrador' ? $fijosAdmin : [],
            'iconos'     => self::ICONOS,
        ]);
    }

    /** La descripción de cada privilegio empieza con su CU ("CU09 · …"); de ahí se obtiene el paquete. */
    private static function agrupar(array $privilegios): array
    {
        $grupos = array_fill_keys(array_keys(self::PAQUETES), []);
        foreach ($privilegios as $privilegio) {
            $numero = (int) substr((string) $privilegio['descripcion'], 2, 2);
            foreach (self::PAQUETES as $nombre => [$desde, $hasta]) {
                if ($numero >= $desde && $numero <= $hasta) {
                    $grupos[$nombre][] = $privilegio;
                    break;
                }
            }
        }
        return array_filter($grupos);
    }
}
