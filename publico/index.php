<?php

declare(strict_types=1);

// Punto de entrada único: toda solicitud pasa por aquí (FallbackResource / .htaccess).
require dirname(__DIR__) . '/app/arranque.php';

(new VidaSalud\Nucleo\Aplicacion())->ejecutar();
