<?php

declare(strict_types=1);

use VidaSalud\Paquetes\P2_Agenda\Controladores\Ccita;
use VidaSalud\Paquetes\P2_Agenda\Controladores\Cdisponibilidad;
use VidaSalud\Paquetes\P2_Agenda\Controladores\Cespecialidad;
use VidaSalud\Paquetes\P2_Agenda\Controladores\Chorario;

// P2 Gestión de agenda y reservas (CU06–CU10).
// Formato: [método, ruta, [Controlador::class, 'método'], privilegio, opciones]
return [
    // CU06 · Gestionar especialidades
    ['GET', '/especialidades', [Cespecialidad::class, 'listar'], 'especialidades.gestionar'],
    ['POST', '/especialidades', [Cespecialidad::class, 'crear'], 'especialidades.gestionar'],
    ['PUT', '/especialidades/{id}', [Cespecialidad::class, 'actualizar'], 'especialidades.gestionar'],
    ['POST', '/especialidades/{id}/desactivar', [Cespecialidad::class, 'desactivar'], 'especialidades.gestionar'],
    ['POST', '/especialidades/{id}/activar', [Cespecialidad::class, 'activar'], 'especialidades.gestionar'],

    // CU07 · Configurar horarios y bloqueos
    ['GET', '/horarios', [Chorario::class, 'mostrar'], 'horarios.gestionar'],
    ['POST', '/horarios', [Chorario::class, 'crearVentana'], 'horarios.gestionar'],
    ['POST', '/horarios/bloqueos/descartar', [Chorario::class, 'descartarBloqueo'], 'horarios.gestionar'],
    ['POST', '/horarios/bloqueos/{id}/eliminar', [Chorario::class, 'quitarBloqueo'], 'horarios.gestionar'],
    ['PUT', '/horarios/{id}', [Chorario::class, 'modificarVentana'], 'horarios.gestionar'],
    ['POST', '/horarios/{id}/desactivar', [Chorario::class, 'quitarVentana'], 'horarios.gestionar'],
    ['POST', '/horarios/{medico}/bloqueos', [Chorario::class, 'bloquear'], 'horarios.gestionar'],

    // CU08 · Consultar disponibilidad (JSON para el calendario y los chips de horario)
    ['GET', '/api/disponibilidad', [Cdisponibilidad::class, 'cupos'], 'disponibilidad.consultar'],

    // CU09 · Reservar cita médica
    ['GET', '/citas/nueva', [Ccita::class, 'mostrarReserva'], 'citas.reservar'],
    ['POST', '/citas', [Ccita::class, 'reservar'], 'citas.reservar'],
    ['GET', '/citas/{id}/comprobante', [Ccita::class, 'comprobante'], 'autenticado'],

    // CU10 · Reprogramar cita médica
    ['GET', '/citas/{id}/reprogramar', [Ccita::class, 'mostrarReprogramar'], 'citas.reprogramar'],
    ['POST', '/citas/{id}/reprogramar', [Ccita::class, 'reprogramar'], 'citas.reprogramar'],
];
