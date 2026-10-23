<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Controladores;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ebloqueo_agenda;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eespecialidad;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehistorial_cita;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehorario_atencion;
use VidaSalud\Paquetes\P2_Agenda\Vistas\Vcita;

/** CU09 · Reservar cita médica y CU10 · Reprogramar cita médica (ambos «include» CU08). */
final class Ccita extends Controlador
{
    private const NO_DISPONIBLE = 'El horario seleccionado ya no está disponible. Elige otro cupo.';

    // ---------------------------------------------------------------- CU09

    public function mostrarReserva(): Respuesta
    {
        $usuario = $this->usuario();
        $pacientes = new Epaciente();
        $paciente = null;
        if ($usuario['perfil'] === 'paciente') {
            $paciente = $pacientes->buscarDetalle((int) $usuario['datos_personales_id']);
            if (!$paciente || !$paciente['activo']) {
                throw new ExcepcionAcceso('Tu registro de paciente está inactivo. Comunícate con recepción.');
            }
        }
        return (new Vcita())->mostrarReserva(
            (new Eespecialidad())->activas(),
            (new Emedico())->activosConEspecialidades(),
            $paciente ? [] : $pacientes->activosParaReserva(),
            $paciente,
            $this->solicitud->solo(['especialidad_id', 'medico_id', 'fecha', 'paciente_id'])
        );
    }

    /** Flujo principal 3–9: verifica de nuevo el cupo dentro de una transacción y registra cita + historial. */
    public function reservar(): Respuesta
    {
        $usuario = $this->usuario();
        $esPaciente = $usuario['perfil'] === 'paciente';
        $datos = $this->validar([
            'paciente_id'     => $esPaciente ? 'opcional' : 'requerido|entero',
            'especialidad_id' => 'requerido|entero',
            'medico_id'       => 'requerido|entero',
            'inicio'          => 'requerido|fecha_hora',
            'fin'             => 'requerido|fecha_hora',
            'modalidad'       => 'requerido|en:presencial,virtual',
            'motivo'          => 'opcional|max:500',
        ]);

        // E4: el paciente reserva solo para sí mismo.
        $pacienteId = $esPaciente ? (int) $usuario['datos_personales_id'] : (int) $datos['paciente_id'];
        $paciente = (new Epaciente())->buscarDetalle($pacienteId);
        if (!$paciente) {
            throw new ExcepcionValidacion(['paciente_id' => 'Selecciona un paciente registrado.'], $this->solicitud->todo());
        }
        if (!$paciente['activo']) {
            throw new ExcepcionNegocio('El paciente está inactivo y no puede recibir nuevas citas.');
        }
        $medicoId = (int) $datos['medico_id'];
        $especialidad = (new Eespecialidad())->buscarPorId((int) $datos['especialidad_id']);
        [$inicio, $fin] = self::intervalo($datos['inicio'], $datos['fin']);
        $motivo = trim((string) ($datos['motivo'] ?? ''));

        $citaId = BaseDatos::transaccion(function () use ($pacienteId, $medicoId, $especialidad, $inicio, $fin, $motivo, $datos, $usuario): int {
            $citas = new Ecita_medica();
            $citas->bloquearAgendaDe($medicoId);
            // E1–E3: el cupo debe seguir libre, dentro de una ventana vigente, no bloqueado ni pasado.
            if (!$especialidad || !(new Cdisponibilidad($this->solicitud))->esCupoLibre($medicoId, (int) $especialidad['id'], $inicio, $fin)) {
                throw new ExcepcionNegocio(self::NO_DISPONIBLE);
            }
            if ($codigo = $citas->pacienteTieneCruce($pacienteId, $inicio, $fin)) {
                throw new ExcepcionNegocio("El paciente ya tiene la cita $codigo en ese horario.");
            }
            $citaId = $citas->insertar([
                'codigo'           => $citas->siguienteCodigo(),
                'paciente_id'      => $pacienteId,
                'medico_id'        => $medicoId,
                'especialidad_id'  => (int) $especialidad['id'],
                'inicio'           => $inicio,
                'fin'              => $fin,
                'motivo'           => $motivo !== '' ? $motivo : null,
                'modalidad'        => $datos['modalidad'],
                'importe_acordado' => $especialidad['importe_consulta'],
                'creada_por'       => (int) $usuario['id'],
            ]);
            (new Ehistorial_cita())->registrarCreacion($citaId, (int) $usuario['id'], $inicio, $fin);
            Bitacora::registrar('reservar', 'cita_medica', $citaId);
            return $citaId;
        });

        $cita = (new Ecita_medica())->buscarDetalle($citaId);
        return $this->redirigir('/citas/' . $citaId . '/comprobante', 'exito', 'Cita ' . $cita['codigo'] . ' reservada para el ' . fecha_corta($inicio) . ' a las ' . hora_corta($inicio) . '.');
    }

    public function comprobante(string $id): Respuesta
    {
        $cita = $this->citaAccesible($id);
        return (new Vcita())->mostrarComprobante($cita, (new Ehistorial_cita())->deCita((int) $cita['id']));
    }

    // ---------------------------------------------------------------- CU10

    public function mostrarReprogramar(string $id): Respuesta
    {
        $cita = $this->citaAccesible($id);
        self::exigirReprogramable($cita);
        $medicoId = (int) $cita['medico_id'];
        return (new Vcita())->mostrarReprogramar(
            $cita,
            (new Ehorario_atencion())->diasDeAtencion($medicoId),
            array_column((new Ebloqueo_agenda())->proximosDe($medicoId), 'fecha')
        );
    }

    /** Flujo principal 5–8: nuevo cupo del mismo médico y especialidad; se conserva el intervalo anterior. */
    public function reprogramar(string $id): Respuesta
    {
        $cita = $this->citaAccesible($id);
        $datos = $this->validar([
            'inicio' => 'requerido|fecha_hora',
            'fin'    => 'requerido|fecha_hora',
            'motivo' => 'requerido|min:5|max:500',
        ]);
        [$inicio, $fin] = self::intervalo($datos['inicio'], $datos['fin']);
        if ($inicio === self::normalizar($cita['inicio'])) {
            throw new ExcepcionValidacion(['inicio' => 'El nuevo horario es igual al actual.'], $this->solicitud->todo());
        }
        $usuarioId = (int) $this->usuario()['id'];

        BaseDatos::transaccion(function () use ($cita, $inicio, $fin, $datos, $usuarioId): void {
            $citas = new Ecita_medica();
            $citas->bloquearAgendaDe((int) $cita['medico_id']);
            $actual = $citas->paraActualizar((int) $cita['id']);
            self::exigirReprogramable($actual);
            if (!(new Cdisponibilidad($this->solicitud))->esCupoLibre((int) $actual['medico_id'], (int) $actual['especialidad_id'], $inicio, $fin)) {
                throw new ExcepcionNegocio(self::NO_DISPONIBLE);
            }
            if ($codigo = $citas->pacienteTieneCruce((int) $actual['paciente_id'], $inicio, $fin, (int) $actual['id'])) {
                throw new ExcepcionNegocio("El paciente ya tiene la cita $codigo en ese horario.");
            }
            $citas->cambiarIntervalo((int) $actual['id'], $inicio, $fin);
            (new Ehistorial_cita())->registrarReprogramacion($actual, $usuarioId, $inicio, $fin, trim($datos['motivo']));
            Bitacora::registrar('reprogramar', 'cita_medica', (int) $actual['id']);
        });

        return $this->redirigir('/citas/' . $cita['id'] . '/comprobante', 'exito', 'Cita ' . $cita['codigo'] . ' reprogramada para el ' . fecha_corta($inicio) . ' a las ' . hora_corta($inicio) . '.');
    }

    // ---------------------------------------------------------------- reglas comunes

    /**
     * La cita existe y el usuario puede verla: el paciente solo las suyas (E4 de CU10),
     * el médico solo las de su agenda; recepción y administración, todas.
     */
    private function citaAccesible(string $id): array
    {
        $cita = (new Ecita_medica())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('La cita no existe.');
        $usuario = $this->usuario();
        $propia = match ($usuario['perfil']) {
            'paciente' => (int) $cita['paciente_id'] === (int) $usuario['datos_personales_id'],
            'medico'   => (int) $cita['medico_id'] === (int) $usuario['datos_personales_id'],
            default    => true,
        };
        if (!$propia) {
            throw new ExcepcionAcceso('No tienes acceso a esta cita.');
        }
        return $cita;
    }

    /** E1 de CU10: solo citas programadas o confirmadas cuyo inicio aún no llegó. */
    private static function exigirReprogramable(?array $cita): void
    {
        if (!$cita || !in_array((int) $cita['estado_cita_id'], [Ecita_medica::PROGRAMADA, Ecita_medica::CONFIRMADA], true)) {
            throw new ExcepcionNegocio('Solo se pueden reprogramar citas programadas o confirmadas. Para una cita cancelada reserva una nueva.');
        }
        if (new DateTimeImmutable($cita['inicio']) <= new DateTimeImmutable('now')) {
            throw new ExcepcionNegocio('La cita ya comenzó o pasó; no se puede reprogramar.');
        }
    }

    /** Intervalo en formato ISO con la zona de la clínica; rechaza textos que no son fechas. */
    private static function intervalo(string $inicio, string $fin): array
    {
        try {
            $intervalo = [self::normalizar($inicio), self::normalizar($fin)];
        } catch (Throwable) {
            throw new ExcepcionValidacion(['inicio' => 'Selecciona un horario disponible.']);
        }
        if ($intervalo[1] <= $intervalo[0]) {
            throw new ExcepcionValidacion(['inicio' => 'Selecciona un horario disponible.']);
        }
        return $intervalo;
    }

    private static function normalizar(string $fecha): string
    {
        return (new DateTimeImmutable($fecha))->setTimezone(new DateTimeZone(Cdisponibilidad::ZONA))->format(DATE_ATOM);
    }
}
