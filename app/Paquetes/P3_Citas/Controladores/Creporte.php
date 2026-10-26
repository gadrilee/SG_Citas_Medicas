<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Controladores;

use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eespecialidad;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eestado_cita;
use VidaSalud\Paquetes\P3_Citas\Vistas\Vreporte;

/**
 * CU15 · Generar reportes administrativos. Se calculan sobre los datos existentes, sin tablas de totales.
 */
final class Creporte extends Controlador
{
    public const TIPOS = [
        'citas'     => ['Citas', 'Citas por fecha, médico, especialidad y estado'],
        'resumen'   => ['Asistencia', 'Atendidas, canceladas e inasistencias del período'],
        'pacientes' => ['Pacientes', 'Pacientes registrados en el período'],
        'agenda'    => ['Agenda diaria', 'Agenda de un día por médico'],
    ];

    public function inicio(): Respuesta
    {
        return $this->redirigir('/reportes/citas');
    }

    /** Pasos 2–4: tipo, rango y filtros; E1 si la fecha final es anterior a la inicial. */
    public function generar(string $tipo): Respuesta
    {
        if (!isset(self::TIPOS[$tipo])) {
            throw new ExcepcionNoEncontrado('El reporte solicitado no existe.');
        }
        $filtros = $this->solicitud->solo(['desde', 'hasta', 'fecha', 'medico_id', 'especialidad_id', 'estado_cita_id']) + [
            'desde' => date('Y-m-01'),
            'hasta' => date('Y-m-t'),
            'fecha' => date('Y-m-d'),
        ];
        $errores = Validador::validar($filtros, [
            'desde'           => 'requerido|fecha',
            'hasta'           => 'requerido|fecha',
            'fecha'           => 'requerido|fecha',
            'medico_id'       => 'opcional|entero',
            'especialidad_id' => 'opcional|entero',
            'estado_cita_id'  => 'opcional|entero',
        ]);
        if (!$errores && $filtros['hasta'] < $filtros['desde']) {
            $errores['hasta'] = 'La fecha final no puede ser anterior a la inicial.';
        }

        $datos = $errores ? [] : $this->datos($tipo, $filtros);
        return (new Vreporte())->mostrar($tipo, self::TIPOS, $filtros, $datos, [
            'medicos'        => (new Emedico())->activosConEspecialidades(),
            'especialidades' => (new Eespecialidad())->activas(),
            'estados'        => (new Eestado_cita())->todos(),
        ], $errores);
    }

    private function datos(string $tipo, array $filtros): array
    {
        $citas = new Ecita_medica();
        $periodo = array_intersect_key($filtros, array_flip(['desde', 'hasta', 'medico_id', 'especialidad_id', 'estado_cita_id']));
        return match ($tipo) {
            'citas' => [
                'citas'   => $citas->paraReporte($periodo),
                'totales' => $citas->totalesPorEstado($periodo),
            ],
            'resumen' => [
                'totales'          => $citas->totalesPorEstado($periodo),
                'porMedico'        => $citas->resumenPor('medico', $periodo),
                'porEspecialidad'  => $citas->resumenPor('especialidad', $periodo),
            ],
            'pacientes' => [
                'pacientes' => (new Epaciente())->registradosEntre($filtros['desde'], $filtros['hasta']),
            ],
            'agenda' => [
                'citas' => $citas->paraReporte(['fecha' => $filtros['fecha']] + array_intersect_key($filtros, array_flip(['medico_id', 'especialidad_id', 'estado_cita_id']))),
            ],
        };
    }
}
