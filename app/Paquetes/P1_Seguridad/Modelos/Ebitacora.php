<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Lectura de la bitácora (CU03). La escritura la hace VidaSalud\Nucleo\Bitacora desde cada caso de uso. */
final class Ebitacora extends Modelo
{
    protected const TABLA = 'bitacora';

    public function listar(array $filtros, int $pagina): array
    {
        $condiciones = ['TRUE'];
        $parametros = [];
        if (!empty($filtros['desde'])) {
            $condiciones[] = 'b.fecha_hora >= CAST(:desde AS date)';
            $parametros['desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'b.fecha_hora < CAST(:hasta AS date) + 1';
            $parametros['hasta'] = $filtros['hasta'];
        }
        if (!empty($filtros['usuario_id'])) {
            $condiciones[] = 'b.usuario_id = :usuario';
            $parametros['usuario'] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = 'b.accion = :accion';
            $parametros['accion'] = $filtros['accion'];
        }
        if (!empty($filtros['entidad'])) {
            $condiciones[] = 'b.entidad = :entidad';
            $parametros['entidad'] = $filtros['entidad'];
        }
        return $this->paginar(
            'b.id, b.fecha_hora, b.accion, b.entidad, b.registro_id, host(b.ip) AS ip, u.nombre_usuario',
            'FROM bitacora b JOIN usuario u ON u.id = b.usuario_id WHERE ' . implode(' AND ', $condiciones),
            $parametros,
            'b.fecha_hora DESC, b.id DESC',
            $pagina,
            15
        );
    }

    public function ultimas(int $cantidad): array
    {
        return $this->consultar(
            'SELECT b.fecha_hora, b.accion, b.entidad, b.registro_id, u.nombre_usuario
               FROM bitacora b JOIN usuario u ON u.id = b.usuario_id
              ORDER BY b.fecha_hora DESC, b.id DESC LIMIT ' . $cantidad
        );
    }

    /** Valores distintos para los filtros: usuarios con registros, acciones y entidades. */
    public function opcionesDeFiltro(): array
    {
        return [
            'usuarios'  => $this->consultar('SELECT DISTINCT u.id, u.nombre_usuario FROM bitacora b JOIN usuario u ON u.id = b.usuario_id ORDER BY u.nombre_usuario'),
            'acciones'  => array_column($this->consultar('SELECT DISTINCT accion FROM bitacora ORDER BY accion'), 'accion'),
            'entidades' => array_column($this->consultar('SELECT DISTINCT entidad FROM bitacora ORDER BY entidad'), 'entidad'),
        ];
    }
}
