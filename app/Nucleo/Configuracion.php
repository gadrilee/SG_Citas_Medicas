<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

final class Configuracion
{
    private static array $valores = [];

    /** Carga un archivo .env (solo en desarrollo); no reemplaza variables ya definidas. */
    public static function cargarEntorno(string $archivo): void
    {
        if (!is_file($archivo)) {
            return;
        }
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            if (getenv($clave) === false) {
                putenv($clave . '=' . trim($valor, "\"'"));
            }
        }
    }

    public static function cargar(string $archivo): void
    {
        self::$valores = require $archivo;
    }

    /** Lee una clave con notación de puntos: obtener('base_datos.url'). */
    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        $valor = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }

    public static function esLocal(): bool
    {
        return self::obtener('entorno') === 'local';
    }
}
