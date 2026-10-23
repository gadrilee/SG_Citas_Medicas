<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Operaciones protegidas del sistema (una o dos por caso de uso). */
final class Eprivilegio extends Modelo
{
    protected const TABLA = 'privilegio';

    /** Privilegios con la marca "asignado" para el perfil indicado. */
    public function listarParaPerfil(int $perfilId): array
    {
        return $this->consultar(
            'SELECT pr.id, pr.nombre, pr.descripcion, COALESCE(pp.activo, FALSE) AS asignado
               FROM privilegio pr
               LEFT JOIN perfil_privilegio pp ON pp.privilegio_id = pr.id AND pp.perfil_id = ?
              ORDER BY pr.id',
            [$perfilId]
        );
    }
}
