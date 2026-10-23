<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use PDO;
use RuntimeException;
use Throwable;

/** Conexión única a PostgreSQL mediante PDO + pdo_pgsql. */
final class BaseDatos
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            [$dsn, $usuario, $clave] = self::interpretarUrl((string) Configuracion::obtener('base_datos.url'));
            self::$conexion = new PDO($dsn, $usuario, $clave, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Las reglas del día y la agenda se interpretan en la hora de Bolivia.
            self::$conexion->exec("SET TIME ZONE 'America/La_Paz'");
        }
        return self::$conexion;
    }

    /**
     * Ejecuta $operacion dentro de una transacción: confirma si termina bien y revierte si lanza
     * una excepción. Se usa en reservas, reprogramaciones, consultas y pagos.
     */
    public static function transaccion(callable $operacion): mixed
    {
        $bd = self::conexion();
        $bd->beginTransaction();
        try {
            $resultado = $operacion($bd);
            $bd->commit();
            return $resultado;
        } catch (Throwable $error) {
            if ($bd->inTransaction()) {
                $bd->rollBack();
            }
            throw $error;
        }
    }

    /** postgres://usuario:clave@host:puerto/base?sslmode=require → [dsn, usuario, clave] */
    public static function interpretarUrl(string $url): array
    {
        $partes = parse_url($url);
        if (!$url || !$partes || !isset($partes['host'], $partes['path'])) {
            throw new RuntimeException('DATABASE_URL no está configurada o no es válida.');
        }
        parse_str($partes['query'] ?? '', $opciones);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $partes['host'],
            $partes['port'] ?? 5432,
            ltrim($partes['path'], '/')
        );
        if (!empty($opciones['sslmode'])) {
            $dsn .= ';sslmode=' . $opciones['sslmode'];
        }
        return [$dsn, urldecode($partes['user'] ?? ''), urldecode($partes['pass'] ?? '')];
    }
}
