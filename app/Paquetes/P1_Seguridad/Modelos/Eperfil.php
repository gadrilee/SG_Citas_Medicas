<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Catálogo fijo de los cuatro perfiles (1 administrador, 2 recepcionista, 3 médico, 4 paciente). */
final class Eperfil extends Modelo
{
    protected const TABLA = 'perfil';

    public const ADMINISTRADOR = 1;
    public const RECEPCIONISTA = 2;
    public const MEDICO = 3;
    public const PACIENTE = 4;

    public function listarConConteo(): array
    {
        return $this->consultar(
            'SELECT pf.id, pf.nombre, pf.descripcion, count(u.id) FILTER (WHERE u.activo) AS usuarios
               FROM perfil pf LEFT JOIN usuario u ON u.perfil_id = pf.id
              GROUP BY pf.id ORDER BY pf.id'
        );
    }
}
