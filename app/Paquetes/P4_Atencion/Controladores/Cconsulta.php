<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Controladores;

use DateTimeImmutable;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehistorial_cita;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Econsulta;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eestudio_laboratorio;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Etratamiento;
use VidaSalud\Paquetes\P4_Atencion\Vistas\Vconsulta;

/** CU17 · Registrar consulta y tratamiento. Extendido por CU18 (Cestudio) en «solicitar estudios». */
final class Cconsulta extends Controlador
{
    /**
     * Pasos 1–3: datos del paciente, su historia y el formulario. Si la cita ya tiene consulta se muestra
     * en modo lectura (una cita tiene una única consulta).
     */
    public function mostrar(string $id): Respuesta
    {
        $cita = $this->citaDelMedico($id);
        $pacienteId = (int) $cita['paciente_id'];
        // La historia y los resultados solo se muestran para los pacientes del día (E1 de CU16 y CU19).
        $deHoy = self::esDeHoy($cita);
        $contexto = [
            (new Epaciente())->buscarDetalle($pacienteId),
            $deHoy ? (new Eficha_medica())->conResponsable($pacienteId) : null,
            $deHoy ? (new Econsulta())->dePaciente($pacienteId) : [],
            $deHoy ? (new Eestudio_laboratorio())->publicadosDePaciente($pacienteId) : [],   // CU19 paso 7
        ];

        $consulta = (new Econsulta())->deCita((int) $cita['id']);
        if ($consulta) {
            return (new Vconsulta())->mostrarDetalle(
                $cita,
                $consulta,
                (new Etratamiento())->deConsulta((int) $consulta['id']),
                (new Eestudio_laboratorio())->deConsulta((int) $consulta['id']),
                ...$contexto
            );
        }
        self::exigirAtendible($cita);
        return (new Vconsulta())->mostrarFormulario($cita, ...$contexto);
    }

    /** Pasos 4–8: valida todo y, en UNA transacción, crea consulta, tratamientos y estudios y pasa la cita a Atendida. */
    public function registrar(string $id): Respuesta
    {
        $cita = $this->citaDelMedico($id);
        $datos = $this->validar(['observaciones' => 'opcional|max:4000', 'diagnostico' => 'opcional|max:2000'])
            + ['observaciones' => '', 'diagnostico' => ''];
        $datos = array_map(static fn ($valor): string => trim((string) $valor), $datos);

        $errores = [];
        if ($datos['observaciones'] === '' && $datos['diagnostico'] === '') {
            $errores['diagnostico'] = 'Registra al menos las observaciones o el diagnóstico.';   // E3
        }
        [$tratamientos, $erroresTratamiento] = $this->tratamientosDelFormulario();
        $cestudio = new Cestudio($this->solicitud);
        [$estudios, $erroresEstudio] = $cestudio->estudiosDelFormulario();
        $errores += $erroresTratamiento + $erroresEstudio;
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        $usuarioId = (int) $this->usuario()['id'];
        BaseDatos::transaccion(function () use ($cita, $datos, $tratamientos, $estudios, $cestudio, $usuarioId): void {
            $citas = new Ecita_medica();
            $actual = $citas->paraActualizar((int) $cita['id']);
            self::exigirAtendible($actual);

            $consultaId = (new Econsulta())->registrar((int) $actual['id'], $datos['observaciones'], $datos['diagnostico']);
            foreach ($tratamientos as $tratamiento) {
                (new Etratamiento())->registrar($consultaId, $tratamiento);
            }
            $cestudio->registrar($consultaId, $estudios);   // punto de extensión: CU18

            $citas->cambiarEstado((int) $actual['id'], Ecita_medica::ATENDIDA);
            (new Ehistorial_cita())->registrarCambioEstado($actual, $usuarioId, Ecita_medica::ATENDIDA);
            Bitacora::registrar('registrar', 'consulta', $consultaId);
        });

        $mensaje = 'Consulta registrada: la cita ' . $cita['codigo'] . ' quedó Atendida.';
        if ($estudios) {
            $mensaje .= ' Se solicitaron ' . count($estudios) . ' estudio(s) de laboratorio.';
        }
        return $this->redirigir('/citas/' . $cita['id'] . '/consulta', 'exito', $mensaje);
    }

    /** E1: la cita existe y es del médico que inició sesión. */
    private function citaDelMedico(string $id): array
    {
        $cita = (new Ecita_medica())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('La cita no existe.');
        if ((int) $cita['medico_id'] !== (int) $this->usuario()['datos_personales_id']) {
            throw new ExcepcionAcceso('Solo puedes atender las citas de tu agenda.');
        }
        return $cita;
    }

    /** E1 y E2: solo citas del día que estén Programadas o Confirmadas. */
    private static function exigirAtendible(?array $cita): void
    {
        $estado = (int) ($cita['estado_cita_id'] ?? 0);
        if (!Ecita_medica::transicionPermitida($estado, Ecita_medica::ATENDIDA)) {
            $nombre = [Ecita_medica::ATENDIDA => 'ya fue atendida', Ecita_medica::CANCELADA => 'está cancelada', Ecita_medica::INASISTENCIA => 'se registró como inasistencia'][$estado] ?? 'no está vigente';
            throw new ExcepcionNegocio('La cita ' . $nombre . '; no admite registrar una consulta.');
        }
        if (!self::esDeHoy($cita)) {
            throw new ExcepcionNegocio('Solo se registra la consulta de las citas del día.');
        }
    }

    private static function esDeHoy(array $cita): bool
    {
        return (new DateTimeImmutable($cita['inicio']))->format('Y-m-d') === date('Y-m-d');
    }

    /**
     * Paso 5: indicaciones de tratamiento (tratamientos[i][indicaciones|fecha_inicio|fecha_fin]).
     * A1: sin filas, la consulta se guarda sin tratamiento. E4: fin anterior al inicio.
     */
    private function tratamientosDelFormulario(): array
    {
        $tratamientos = [];
        $errores = [];
        foreach ((array) $this->solicitud->entrada('tratamientos', []) as $i => $fila) {
            $fila = array_map(static fn ($valor): string => trim((string) $valor), (array) $fila + ['indicaciones' => '', 'fecha_inicio' => '', 'fecha_fin' => '']);
            if ($fila['indicaciones'] === '' && $fila['fecha_inicio'] === '' && $fila['fecha_fin'] === '') {
                continue;
            }
            $erroresFila = Validador::validar($fila, ['indicaciones' => 'requerido|max:1000', 'fecha_inicio' => 'opcional|fecha', 'fecha_fin' => 'opcional|fecha']);
            if (!$erroresFila && $fila['fecha_inicio'] !== '' && $fila['fecha_fin'] !== '' && $fila['fecha_fin'] < $fila['fecha_inicio']) {
                $erroresFila['fecha_fin'] = 'La fecha de fin no puede ser anterior a la de inicio.';
            }
            foreach ($erroresFila as $campo => $mensaje) {
                $errores["tratamientos[$i][$campo]"] = $mensaje;
            }
            $tratamientos[] = $fila;
        }
        return [$tratamientos, $errores];
    }
}
