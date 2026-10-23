<?php

declare(strict_types=1);

use VidaSalud\Compartido\Controladores\Cinicio;

// Rutas que no pertenecen a un caso de uso: portada, panel de inicio por perfil y verificación de salud.
return [
    ['GET', '/', [Cinicio::class, 'inicio'], null],
    ['GET', '/panel', [Cinicio::class, 'panel'], 'autenticado'],
    ['GET', '/salud', [Cinicio::class, 'salud'], null],
];
