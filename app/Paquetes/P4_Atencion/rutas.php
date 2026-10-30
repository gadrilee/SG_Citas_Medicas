<?php

declare(strict_types=1);

use VidaSalud\Paquetes\P4_Atencion\Controladores\Cconsulta;
use VidaSalud\Paquetes\P4_Atencion\Controladores\Cficha_medica;
use VidaSalud\Paquetes\P4_Atencion\Controladores\Cpago;
use VidaSalud\Paquetes\P4_Atencion\Controladores\Cresultado;

// P4 Atención clínica y pagos (CU16–CU20).
// Formato: [método, ruta, [Controlador::class, 'método'], privilegio, opciones]
return [
    // CU16 · Gestionar historia clínica
    ['GET', '/pacientes/{id}/historia', [Cficha_medica::class, 'mostrar'], 'historia.consultar'],
    ['POST', '/pacientes/{id}/historia', [Cficha_medica::class, 'actualizar'], 'historia.actualizar'],
    ['GET', '/mi-historia', [Cficha_medica::class, 'miHistoria'], 'historia.consultar'],

    // CU17 · Registrar consulta y tratamiento (CU18, solicitar estudios, va dentro del mismo formulario)
    ['GET', '/citas/{id}/consulta', [Cconsulta::class, 'mostrar'], 'consultas.registrar'],
    ['POST', '/citas/{id}/consulta', [Cconsulta::class, 'registrar'], 'consultas.registrar'],

    // CU19 · Gestionar resultados de laboratorio
    ['GET', '/estudios', [Cresultado::class, 'listar'], 'resultados.publicar'],
    ['POST', '/estudios/{id}/en-proceso', [Cresultado::class, 'marcarEnProceso'], 'resultados.publicar'],
    ['POST', '/estudios/{id}/publicar', [Cresultado::class, 'publicar'], 'resultados.publicar'],
    ['GET', '/estudios/{id}/resultado', [Cresultado::class, 'informe'], 'resultados.consultar'],
    ['GET', '/estudios/{id}/informe', [Cresultado::class, 'descargar'], 'resultados.consultar'],
    ['GET', '/mis-resultados', [Cresultado::class, 'misResultados'], 'resultados.consultar'],

    // CU20 · Gestionar pagos de citas
    ['GET', '/pagos', [Cpago::class, 'listar'], 'pagos.gestionar'],
    ['GET', '/citas/{id}/pagar', [Cpago::class, 'mostrarPagar'], 'pagos.gestionar'],
    ['POST', '/citas/{id}/pagar', [Cpago::class, 'iniciar'], 'pagos.gestionar'],
    ['GET', '/pagos/retorno', [Cpago::class, 'retorno'], 'pagos.gestionar'],
    ['POST', '/pagos/webhook', [Cpago::class, 'webhook'], null, ['csrf' => false]],
    ['POST', '/pagos/simulada/{id}', [Cpago::class, 'cobrarSimulada'], 'pagos.gestionar'],
    ['GET', '/pagos/{id}', [Cpago::class, 'comprobante'], 'pagos.gestionar'],
    ['POST', '/pagos/{id}/verificar', [Cpago::class, 'verificar'], 'pagos.gestionar'],
    ['POST', '/pagos/{id}/anular', [Cpago::class, 'anular'], 'pagos.gestionar'],
    ['POST', '/pagos/{id}/devolver', [Cpago::class, 'devolver'], 'pagos.gestionar'],
];
