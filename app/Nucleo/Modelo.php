<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use PDO;

/**
 * Base de los modelos de entidad (E*). Cada modelo representa una tabla y concentra sus consultas
 * SQL parametrizadas. Los nombres de columna vienen siempre del código, nunca del usuario.
 */
abstract class Modelo
{
    /** Nombre de la tabla en PostgreSQL. */
    protected const TABLA = '';

    /** Clave primaria simple; las tablas con PK compuesta sobrescriben sus métodos. */
    protected const CLAVE = 'id';

    protected PDO $bd;

    public function __construct(?PDO $bd = null)
    {
        $this->bd = $bd ?? BaseDatos::conexion();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->primero('SELECT * FROM ' . static::TABLA . ' WHERE ' . static::CLAVE . ' = ?', [$id]);
    }

    /** Inserta una fila y devuelve el valor de la clave primaria generada. */
    public function insertar(array $datos): int
    {
        $columnas = array_keys($datos);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING %s',
            static::TABLA,
            implode(', ', $columnas),
            implode(', ', array_fill(0, count($columnas), '?')),
            static::CLAVE
        );
        $consulta = $this->bd->prepare($sql);
        $consulta->execute(array_values($datos));
        return (int) $consulta->fetchColumn();
    }

    public function actualizar(int $id, array $datos): int
    {
        $asignaciones = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($datos)));
        $sql = 'UPDATE ' . static::TABLA . ' SET ' . $asignaciones . ' WHERE ' . static::CLAVE . ' = ?';
        return $this->ejecutar($sql, [...array_values($datos), $id]);
    }

    /**
     * Consulta paginada. $desde es la parte "FROM … WHERE …" compartida por el conteo y las filas.
     * Devuelve ['filas', 'total', 'pagina', 'paginas', 'desde', 'hasta'].
     */
    protected function paginar(string $columnas, string $desde, array $parametros, string $orden, int $pagina, int $porPagina = 10): array
    {
        $total = (int) $this->valor('SELECT count(*) ' . $desde, $parametros);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $filas = $this->consultar(
            'SELECT ' . $columnas . ' ' . $desde . ' ORDER BY ' . $orden . ' LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina),
            $parametros
        );
        return [
            'filas'   => $filas,
            'total'   => $total,
            'pagina'  => $pagina,
            'paginas' => $paginas,
            'desde'   => $total ? ($pagina - 1) * $porPagina + 1 : 0,
            'hasta'   => ($pagina - 1) * $porPagina + count($filas),
        ];
    }

    protected function consultar(string $sql, array $parametros = []): array
    {
        $consulta = $this->bd->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    protected function primero(string $sql, array $parametros = []): ?array
    {
        $consulta = $this->bd->prepare($sql);
        $consulta->execute($parametros);
        $fila = $consulta->fetch();
        return $fila === false ? null : $fila;
    }

    protected function valor(string $sql, array $parametros = []): mixed
    {
        $consulta = $this->bd->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchColumn();
    }

    protected function ejecutar(string $sql, array $parametros = []): int
    {
        $consulta = $this->bd->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->rowCount();
    }
}
