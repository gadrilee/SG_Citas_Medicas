<?php

declare(strict_types=1);

use VidaSalud\Nucleo\Configuracion;

define('RAIZ', dirname(__DIR__));

// Autocarga PSR-4: VidaSalud\Paquetes\P2_Agenda\Controladores\Ccita → app/Paquetes/P2_Agenda/Controladores/Ccita.php
spl_autoload_register(static function (string $clase): void {
    $prefijo = 'VidaSalud\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }
    $archivo = RAIZ . '/app/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

Configuracion::cargarEntorno(RAIZ . '/.env');
Configuracion::cargar(RAIZ . '/config/config.php');

require RAIZ . '/app/Compartido/ayudantes.php';

date_default_timezone_set(Configuracion::obtener('zona_horaria'));
ini_set('display_errors', Configuracion::obtener('depuracion') ? '1' : '0');
error_reporting(E_ALL);
