<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Controladores;

use DateTimeImmutable;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eestado_cita;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehistorial_cita;
use VidaSalud\Paquetes\P3_Citas\Vistas\Vasistencia;

/** CU12 · Registrar confirmación e inasistencia. Atendida la registra CU17 al guardar la consulta. */
final class Casistencia extends Controlador
{
    /** Pasos 1–2 y A1: citas del día agrupadas por médico, con filtro de médico y estado. */
    public function mostrar(): Respuesta
    {
        $filtros = $this->solicitud->solo(['fecha', 'medico_id', 'estado_cita_id']) + ['fecha' => date('Y-m-d')];
        $errores = Validador::validar($filtros, ['fecha' => 'requerido|fecha', 'medico_id' => 'opcional|entero', 'estado_cita_id' => 'opcional|entero']);
        if ($errores) {
            $filtros['fecha'] = date('Y-m-d');
        }
        $citas = (new Ecita_medica())->delDia(
            $filtros['fecha'],
            ($filtros['medico_id'] ?? '') !== '' ? (int) $filtros['medico_id'] : null,
            ($filtros['estado_cita_id'] ?? '') !== '' ? (int) $filtros['estado_cita_id'] : null
        );

        $porMedico = [];
        foreach ($citas as $cita) {
            $porMedico[$cita['medico']]['especialidad'] = $cita['especialidad'];
            $porMedico[$cita['medico']]['codigo_medico'] = $cita['codigo_medico'];
            $porMedico[$cita['medico']]['citas'][] = $cita;
        }

        return (new Vasistencia())->mostrar(
            $porMedico,
            array_count_values(array_column($citas, 'estado')),
            $filtros,
            (new Emedico())->activosConEspecialidades(),
            (new Eestado_cita())->todos(),
            $errores
        );
    }

    /** Pasos 3–4: Programada → Confirmada mientras el turno no haya terminado. */
    public function confirmar(string $id): Respuesta
    {
        $cita = $this->cambiar($id, Ecita_medica::CONFIRMADA, static function (array $cita): void {
            if (new DateTimeImmutable($cita['fin']) <= new DateTimeImmutable('now')) {
                throw new ExcepcionNegocio('El turno de ' . $cita['codigo'] . ' ya terminó: registra la inasistencia o la consulta.');
            }
        });
        return $this->volver('exito', 'Asistencia confirmada para ' . $cita['codigo'] . ' (' . hora_corta($cita['inicio']) . ').');
    }

    /** Pasos 5–6 y E1: Inasistencia solo cuando la hora de fin ya pasó. */
    public function registrarInasistencia(string $id): Respuesta
    {
        $cita = $this->cambiar($id, Ecita_medica::INASISTENCIA, static function (array $cita): void {
            if (new DateTimeImmutable($cita['fin']) > new DateTimeImmutable('now')) {
                throw new ExcepcionNegocio('El turno de ' . $cita['codigo'] . ' termina a las ' . hora_corta($cita['fin']) . '; la inasistencia se registra después.');
            }
        });
        return $this->volver('exito', 'Inasistencia registrada para ' . $cita['codigo'] . '.');
    }

    /** Aplica la transición dentro de una transacción con la fila bloqueada (E2, E3) y registra historial y bitácora. */
    private function cambiar(string $id, int $estadoNuevo, callable $reglaDeHorario): array
    {
        $usuarioId = (int) $this->usuario()['id'];
        return BaseDatos::transaccion(function () use ($id, $estadoNuevo, $reglaDeHorario, $usuarioId): array {
            $citas = new Ecita_medica();
            $cita = $citas->paraActualizar((int) $id) ?? throw new ExcepcionNoEncontrado('La cita no existe.');
            if (!Ecita_medica::transicionPermitida((int) $cita['estado_cita_id'], $estadoNuevo)) {
                throw new ExcepcionNegocio('La cita ' . $cita['codigo'] . ' está ' . self::nombreEstado((int) $cita['estado_cita_id']) . ' y no admite este cambio.');
            }
            $reglaDeHorario($cita);
            $citas->cambiarEstado((int) $cita['id'], $estadoNuevo);
            (new Ehistorial_cita())->registrarCambioEstado($cita, $usuarioId, $estadoNuevo);
            Bitacora::registrar($estadoNuevo === Ecita_medica::CONFIRMADA ? 'confirmar' : 'registrar_inasistencia', 'cita_medica', (int) $cita['id']);
            return $cita;
        });
    }

    private static function nombreEstado(int $estado): string
    {
        return [1 => 'programada', 2 => 'confirmada', 3 => 'atendida', 4 => 'cancelada', 5 => 'registrada como inasistencia'][$estado] ?? 'en otro estado';
    }
}
