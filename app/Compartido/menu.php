<?php

declare(strict_types=1);

// Menú lateral por perfil (estructura de V0). Cada ítem: [texto, icono Lucide, ruta, privilegio].
// Un ítem solo se muestra si el usuario tiene el privilegio ('autenticado' = cualquier sesión).
return [
    'administrador' => [
        'General' => [
            ['Inicio', 'layout-dashboard', '/panel', 'autenticado'],
        ],
        'Seguridad' => [
            ['Usuarios', 'users', '/usuarios', 'usuarios.gestionar'],
            ['Perfiles y privilegios', 'shield-check', '/perfiles', 'perfiles.gestionar'],
            ['Bitácora', 'scroll-text', '/bitacora', 'bitacora.consultar'],
        ],
        'Clínica' => [
            ['Médicos', 'stethoscope', '/medicos', 'medicos.gestionar'],
            ['Especialidades', 'heart-pulse', '/especialidades', 'especialidades.gestionar'],
            ['Horarios', 'clock', '/horarios', 'horarios.gestionar'],
        ],
        'Seguimiento' => [
            ['Agenda', 'calendar-days', '/agenda', 'agenda.consultar'],
            ['Historial de citas', 'history', '/historial', 'historial.consultar'],
            ['Reportes', 'chart-column', '/reportes', 'reportes.generar'],
        ],
    ],
    'recepcionista' => [
        'General' => [
            ['Inicio', 'layout-dashboard', '/panel', 'autenticado'],
        ],
        'Personas' => [
            ['Pacientes', 'users-round', '/pacientes', 'pacientes.gestionar'],
        ],
        'Citas' => [
            ['Nueva cita', 'calendar-plus', '/citas/nueva', 'citas.reservar'],
            ['Citas', 'calendar-check', '/citas', 'citas.cancelar'],
            ['Control de asistencia', 'user-check', '/asistencia', 'asistencia.registrar'],
            ['Agenda', 'calendar-days', '/agenda', 'agenda.consultar'],
            ['Historial de citas', 'history', '/historial', 'historial.consultar'],
        ],
        'Caja' => [
            ['Pagos', 'wallet', '/pagos', 'pagos.gestionar'],
        ],
    ],
    'medico' => [
        'General' => [
            ['Inicio', 'layout-dashboard', '/panel', 'autenticado'],
        ],
        'Atención' => [
            ['Mi agenda', 'calendar-days', '/agenda', 'agenda.consultar'],
            ['Estudios', 'flask-conical', '/estudios', 'resultados.publicar'],
        ],
        'Cuenta' => [
            ['Mi cuenta', 'user-cog', '/mi-cuenta', 'autenticado'],
        ],
    ],
    'paciente' => [
        'General' => [
            ['Inicio', 'layout-dashboard', '/panel', 'autenticado'],
        ],
        'Mis citas' => [
            ['Nueva cita', 'calendar-plus', '/citas/nueva', 'citas.reservar'],
            ['Mis citas', 'calendar-check', '/mis-citas', 'historial.consultar'],
        ],
        'Mi salud' => [
            ['Mi historia clínica', 'file-heart', '/mi-historia', 'historia.consultar'],
            ['Mis resultados', 'flask-conical', '/mis-resultados', 'resultados.consultar'],
        ],
        'Cuenta' => [
            ['Mi cuenta', 'user-cog', '/mi-cuenta', 'autenticado'],
        ],
    ],
];
