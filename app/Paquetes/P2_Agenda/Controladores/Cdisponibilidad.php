<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Controladores;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ebloqueo_agenda;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehorario_atencion;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Emedico_especialidad;
use VidaSalud\Paquetes\P2_Agenda\Vistas\Vdisponibilidad;

/**
 * CU08 · Consultar disponibilidad. Los cupos no se guardan: se calculan con las ventanas vigentes,
 * la duración del turno, los bloqueos y las citas no canceladas. CU09 y CU10 lo incluyen («include»)
 * para volver a comprobar el cupo en el servidor antes de guardar.
 */
final class Cdisponibilidad extends Controlador
{
    public const ZONA = 'America/La_Paz';
    private const DIAS_SUGERENCIA = 30;

    /** GET /api/disponibilidad?medico_id=&especialidad_id=&fecha=AAAA-MM-DD */
    public function cupos(): Respuesta
    {
        $datos = $this->validar(['medico_id' => 'requerido|entero', 'especialidad_id' => 'requerido|entero', 'fecha' => 'requerido|fecha']);
        $medicoId = (int) $datos['medico_id'];
        $especialidadId = (int) $datos['especialidad_id'];
        $resultado = $this->calcular($medicoId, $especialidadId, $datos['fecha']);

        $hayLibres = (bool) array_filter($resultado['cupos'], static fn (array $cupo): bool => $cupo['libre']);
        $sugerencias = $hayLibres ? [] : $this->proximasFechasConCupo($medicoId, $especialidadId, $datos['fecha'], 3);

        return (new Vdisponibilidad())->responder($datos['fecha'], $resultado, $sugerencias);
    }

    /**
     * Cupos de un día. Devuelve ['cupos' => [['inicio', 'fin', 'libre'], …], 'motivo' => null|texto]
     * con el motivo cuando el día no tiene atención (E1, E2, bloqueo o sin ventana).
     */
    public function calcular(int $medicoId, int $especialidadId, string $fecha): array
    {
        $zona = new DateTimeZone(self::ZONA);
        $ahora = new DateTimeImmutable('now', $zona);
        if ($fecha < $ahora->format('Y-m-d')) {
            return ['cupos' => [], 'motivo' => 'La fecha ya pasó.'];
        }
        if (!(new Emedico_especialidad())->habilitada($medicoId, $especialidadId)) {
            return ['cupos' => [], 'motivo' => 'El médico no atiende esa especialidad o no está activo.'];
        }
        if ((new Ebloqueo_agenda())->existe($medicoId, $fecha)) {
            return ['cupos' => [], 'motivo' => 'El médico no atiende ese día (día bloqueado).'];
        }
        $ventanas = (new Ehorario_atencion())->paraFecha($medicoId, $fecha);
        if (!$ventanas) {
            return ['cupos' => [], 'motivo' => 'El médico no tiene horario de atención ese día.'];
        }

        $inicioDia = new DateTimeImmutable($fecha . ' 00:00', $zona);
        $ocupadas = (new Ecita_medica())->intervalosOcupados(
            $medicoId,
            $inicioDia->format(DATE_ATOM),
            $inicioDia->add(new DateInterval('P1D'))->format(DATE_ATOM)
        );
        return ['cupos' => self::generarTurnos($fecha, $ventanas, $ocupadas, $ahora), 'motivo' => null];
    }

    /** CU09/CU10 paso de verificación: el intervalo elegido es exactamente un cupo libre. */
    public function esCupoLibre(int $medicoId, int $especialidadId, string $inicio, string $fin): bool
    {
        $zona = new DateTimeZone(self::ZONA);
        $inicio = (new DateTimeImmutable($inicio))->setTimezone($zona);
        $fin = (new DateTimeImmutable($fin))->setTimezone($zona);
        foreach ($this->calcular($medicoId, $especialidadId, $inicio->format('Y-m-d'))['cupos'] as $cupo) {
            if ($cupo['libre'] && $cupo['inicio'] === $inicio->format(DATE_ATOM) && $cupo['fin'] === $fin->format(DATE_ATOM)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Divide las ventanas en turnos de su duración. Un turno está libre si empieza después de "ahora"
     * y no se cruza con una cita no cancelada. Función pura: se prueba sin base de datos.
     *
     * @param array $ventanas [['hora_inicio' => '08:00', 'hora_fin' => '12:00', 'duracion_cita_min' => 15], …]
     * @param array $ocupadas [['inicio' => '2026-11-16 09:00:00-04', 'fin' => '…'], …]
     */
    public static function generarTurnos(string $fecha, array $ventanas, array $ocupadas, DateTimeImmutable $ahora): array
    {
        $zona = new DateTimeZone(self::ZONA);
        $intervalos = array_map(static fn (array $cita): array => [
            new DateTimeImmutable($cita['inicio']),
            new DateTimeImmutable($cita['fin']),
        ], $ocupadas);

        $cupos = [];
        foreach ($ventanas as $ventana) {
            $duracion = new DateInterval('PT' . (int) $ventana['duracion_cita_min'] . 'M');
            $turno = new DateTimeImmutable($fecha . ' ' . $ventana['hora_inicio'], $zona);
            $cierre = new DateTimeImmutable($fecha . ' ' . $ventana['hora_fin'], $zona);
            while (($finTurno = $turno->add($duracion)) <= $cierre) {
                $ocupado = false;
                foreach ($intervalos as [$inicioCita, $finCita]) {
                    if ($inicioCita < $finTurno && $finCita > $turno) {
                        $ocupado = true;
                        break;
                    }
                }
                $cupos[$turno->format(DATE_ATOM)] = [
                    'inicio' => $turno->format(DATE_ATOM),
                    'fin'    => $finTurno->format(DATE_ATOM),
                    'libre'  => !$ocupado && $turno > $ahora,
                ];
                $turno = $finTurno;
            }
        }
        ksort($cupos);
        return array_values($cupos);
    }

    /** A1: cuando un día no tiene cupos, sugiere las siguientes fechas con al menos uno libre. */
    private function proximasFechasConCupo(int $medicoId, int $especialidadId, string $fecha, int $cantidad): array
    {
        $dias = (new Ehorario_atencion())->diasDeAtencion($medicoId);
        $sugerencias = [];
        $dia = new DateTimeImmutable($fecha, new DateTimeZone(self::ZONA));
        for ($i = 1; $i <= self::DIAS_SUGERENCIA && count($sugerencias) < $cantidad; $i++) {
            $dia = $dia->add(new DateInterval('P1D'));
            if (!in_array((int) $dia->format('N'), $dias, true)) {
                continue;
            }
            foreach ($this->calcular($medicoId, $especialidadId, $dia->format('Y-m-d'))['cupos'] as $cupo) {
                if ($cupo['libre']) {
                    $sugerencias[] = $dia->format('Y-m-d');
                    break;
                }
            }
        }
        return $sugerencias;
    }
}
