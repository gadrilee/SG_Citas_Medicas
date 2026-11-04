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
    // Firma las respuestas de la pasarela simulada; en Render se genera sola (render.yaml).
    'clave_app'    => getenv('APP_KEY') ?: hash('sha256', 'vida-salud|' . (getenv('DATABASE_URL') ?: '')),
    // Pasarela con la que se inician los cobros nuevos: simulada | stripe
    'pasarela'     => getenv('PASARELA') ?: 'simulada',
    'moneda'       => 'BOB',
    'stripe'       => [
        // La clave pública (pk_test_…) la usa Stripe.js para dibujar el formulario en la página.
        'publica' => getenv('STRIPE_PUBLIC_KEY') ?: '',
        'secreta' => getenv('STRIPE_SECRET_KEY') ?: '',
        'webhook' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
    ],
];
