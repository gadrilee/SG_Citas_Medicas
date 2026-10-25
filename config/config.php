<?php

declare(strict_types=1);

// Toda la configuración sale de variables de entorno (Render, Docker o el archivo .env local).
return [
    'entorno'      => getenv('APP_ENV') ?: 'produccion',
    'depuracion'   => filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'url'          => rtrim(getenv('APP_URL') ?: '', '/'),
    'zona_horaria' => 'America/La_Paz',
    'base_datos'   => [
        // postgres://usuario:clave@host:puerto/base?sslmode=require
        'url' => getenv('DATABASE_URL') ?: '',
    ],
    'pasarela'     => getenv('PASARELA') ?: 'simulada',
    'moneda'       => 'BOB',
];
