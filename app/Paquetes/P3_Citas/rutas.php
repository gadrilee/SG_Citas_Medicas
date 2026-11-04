<?php

declare(strict_types=1);

use VidaSalud\Paquetes\P2_Agenda\Controladores\Ccita;
use VidaSalud\Paquetes\P3_Citas\Controladores\Cagenda;
use VidaSalud\Paquetes\P3_Citas\Controladores\Casistencia;
use VidaSalud\Paquetes\P3_Citas\Controladores\Chistorial;
use VidaSalud\Paquetes\P3_Citas\Controladores\Creporte;

// P3 Seguimiento de citas y reportes (CU11–CU15).
// Formato: [método, ruta, [Controlador::class, 'método'], privilegio, opciones]
return [
    // CU11 · Cancelar cita médica (Ccita comparte la cita con CU09 y CU10)
    ['GET', '/citas', [Ccita::class, 'mostrarLista'], 'citas.cancelar'],
    ['POST', '/citas/{id}/cancelar', [Ccita::class, 'cancelar'], 'citas.cancelar'],

    // CU12 · Registrar confirmación e inasistencia
    ['GET', '/asistencia', [Casistencia::class, 'mostrar'], 'asistencia.registrar'],
    ['POST', '/asistencia/{id}/confirmar', [Casistencia::class, 'confirmar'], 'asistencia.registrar'],
    ['POST', '/asistencia/{id}/inasistencia', [Casistencia::class, 'registrarInasistencia'], 'asistencia.registrar'],

    // CU13 · Consultar agenda médica
    ['GET', '/agenda', [Cagenda::class, 'mostrar'], 'agenda.consultar'],

    // CU14 · Consultar historial de citas
    ['GET', '/historial', [Chistorial::class, 'buscar'], 'historial.consultar'],
    ['GET', '/mis-citas', [Chistorial::class, 'misCitas'], 'historial.consultar'],

    // CU15 · Generar reportes administrativos
    ['GET', '/reportes', [Creporte::class, 'inicio'], 'reportes.generar'],
    ['GET', '/reportes/{tipo}', [Creporte::class, 'generar'], 'reportes.generar'],
];
