<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

final class Sesion
{
    public static function iniciar(bool $segura): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('vida_salud');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $segura,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function poner(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function quitar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /** Mensaje que se muestra una sola vez en la siguiente página (tipo: exito, error, aviso). */
    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function consumirFlash(): array
    {
        $mensajes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $mensajes;
    }

    /** Errores de validación y valores anteriores del formulario para volver a mostrarlos. */
    public static function guardarFormulario(array $errores, array $entradas): void
    {
        unset($entradas['contrasena'], $entradas['contrasena_confirmacion'], $entradas['contrasena_actual'], $entradas['contrasena_nueva']);
        $_SESSION['_errores'] = $errores;
        $_SESSION['_anterior'] = $entradas;
    }

    public static function consumirFormulario(): array
    {
        $datos = [$_SESSION['_errores'] ?? [], $_SESSION['_anterior'] ?? []];
        unset($_SESSION['_errores'], $_SESSION['_anterior']);
        return $datos;
    }

    /** Se llama al iniciar sesión: cambia el id de sesión para evitar su fijación. */
    public static function iniciarUsuario(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
        unset($_SESSION['_privilegios']);
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
    }
}
