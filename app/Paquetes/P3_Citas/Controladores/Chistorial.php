<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Controladores;

use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eestado_cita;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehistorial_cita;
use VidaSalud\Paquetes\P3_Citas\Vistas\Vhistorial;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epago;

/** CU14 · Consultar historial de citas (solo lectura). El paciente solo ve las suyas. */
final class Chistorial extends Controlador
{
    /** Paso 1: recepción y administración buscan al paciente por CI, nombre o código. */
    public function buscar(): Respuesta
    {
        if ($this->usuario()['perfil'] === 'paciente') {
            return $this->redirigir('/mis-citas');
        }
        $buscar = trim((string) $this->solicitud->consulta('buscar', ''));
        $pacienteId = (int) $this->solicitud->consulta('paciente_id', 0);
        $pacientes = new Epaciente();

        if (!$pacienteId) {
            $resultado = $pacientes->listar($buscar, null, (int) $this->solicitud->consulta('pagina', 1));
            return (new Vhistorial())->mostrarBusqueda($buscar, $resultado);
        }

        $paciente = $pacientes->buscarDetalle($pacienteId) ?? throw new ExcepcionNoEncontrado('El paciente no existe.');
        [$filtros, $citas, $eventos, $errores] = $this->citasDe($pacienteId);
        return (new Vhistorial())->mostrarPaciente($paciente, $citas, $eventos, $filtros, (new Eestado_cita())->todos(), $errores);
    }

    /** «Mis citas» del paciente (E1: siempre su propio registro) con cancelación y reprogramación. */
    public function misCitas(): Respuesta
    {
        if ($this->usuario()['perfil'] !== 'paciente') {
            return $this->redirigir('/historial');
        }
        $pacienteId = (int) $this->usuario()['datos_personales_id'];
        [$filtros, $citas, $eventos, $errores] = $this->citasDe($pacienteId);
        return (new Vhistorial())->mostrarMisCitas(
            $citas,
            $eventos,
            (new Epago())->citasConPagoAprobado(array_column($citas, 'id')),
            $filtros,
            (new Eestado_cita())->todos(),
            $errores
        );
    }

    /** Pasos 2–5: citas del paciente con filtro de rango (E2) y estado, más los eventos de cada una. */
    private function citasDe(int $pacienteId): array
    {
        $filtros = $this->solicitud->solo(['desde', 'hasta', 'estado_cita_id']);
        $errores = Validador::validar($filtros, ['desde' => 'opcional|fecha', 'hasta' => 'opcional|fecha', 'estado_cita_id' => 'opcional|entero']);
        if (!$errores && !empty($filtros['desde']) && !empty($filtros['hasta']) && $filtros['hasta'] < $filtros['desde']) {
            $errores['hasta'] = 'La fecha final no puede ser anterior a la inicial.';
        }
        if ($errores) {
            return [$filtros, [], [], $errores];
        }
        $citas = (new Ecita_medica())->historialDe($pacienteId, $filtros);
        return [$filtros, $citas, (new Ehistorial_cita())->deCitas(array_column($citas, 'id')), []];
    }
}
