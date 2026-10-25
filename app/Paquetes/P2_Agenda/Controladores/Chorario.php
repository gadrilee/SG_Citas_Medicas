<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Sesion;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ebloqueo_agenda;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehorario_atencion;
use VidaSalud\Paquetes\P2_Agenda\Vistas\Vhorario;

/** CU07 · Configurar horarios y bloqueos. */
final class Chorario extends Controlador
{
    private const REGLAS_VENTANA = [
        'dia_semana'        => 'requerido|en:1,2,3,4,5,6,7',
        'hora_inicio'       => 'requerido|hora',
        'hora_fin'          => 'requerido|hora',
        'duracion_cita_min' => 'requerido|entero|entre:5,240',
        'vigente_desde'     => 'requerido|fecha',
        'vigente_hasta'     => 'opcional|fecha',
    ];

    /** Clave de sesión del bloqueo que espera confirmación porque el día tiene citas (paso 7). */
    private const BLOQUEO_PENDIENTE = 'bloqueo_pendiente';

    public function mostrar(): Respuesta
    {
        $medicos = (new Emedico())->activosConEspecialidades();
        if (!$medicos) {
            return (new Vhorario())->mostrar([], null, [], [], null, null, []);
        }
        $medicoId = (int) $this->solicitud->consulta('medico_id', $medicos[0]['id']);
        $medico = current(array_filter($medicos, static fn (array $m): bool => (int) $m['id'] === $medicoId)) ?: $medicos[0];
        $medicoId = (int) $medico['id'];

        $horarios = new Ehorario_atencion();
        $editarId = (int) $this->solicitud->consulta('editar');
        $editar = $editarId ? $horarios->buscarPorId($editarId) : null;
        if ($editar && (int) $editar['medico_id'] !== $medicoId) {
            $editar = null;
        }

        // Bloqueo pendiente de confirmación: se muestran las citas afectadas antes de guardarlo.
        $pendiente = Sesion::obtener(self::BLOQUEO_PENDIENTE);
        $afectadas = [];
        if ($pendiente && (int) $pendiente['medico_id'] === $medicoId) {
            $afectadas = (new Ecita_medica())->noCanceladasEnFecha($medicoId, $pendiente['fecha']);
        } else {
            $pendiente = null;
        }

        return (new Vhorario())->mostrar(
            $medicos,
            $medico,
            $horarios->vigentesDe($medicoId),
            (new Ebloqueo_agenda())->proximosDe($medicoId),
            $editar,
            $pendiente,
            $afectadas
        );
    }

    /** Flujo principal 3–5: nueva ventana semanal. */
    public function crearVentana(): Respuesta
    {
        $medico = $this->medicoActivo((int) $this->solicitud->entrada('medico_id'));
        $ventana = $this->ventanaValidada((int) $medico['id']);
        $id = (new Ehorario_atencion())->insertar($ventana + ['medico_id' => (int) $medico['id']]);
        Bitacora::registrar('crear', 'horario_atencion', $id);
        return $this->redirigir('/horarios?medico_id=' . $medico['id'], 'exito', 'Ventana de atención agregada.');
    }

    /** A1: modificar cierra la vigencia de la ventana actual y crea una nueva; las citas reservadas no cambian. */
    public function modificarVentana(string $id): Respuesta
    {
        $horarios = new Ehorario_atencion();
        $actual = $horarios->buscarPorId((int) $id) ?? throw new ExcepcionNoEncontrado('La ventana no existe.');
        $medicoId = (int) $actual['medico_id'];
        $this->medicoActivo($medicoId);
        $ventana = $this->ventanaValidada($medicoId, (int) $actual['id']);

        $nuevoId = BaseDatos::transaccion(function () use ($horarios, $actual, $ventana, $medicoId): int {
            $horarios->cerrarVigencia((int) $actual['id'], $ventana['vigente_desde']);
            $nuevoId = $horarios->insertar($ventana + ['medico_id' => $medicoId]);
            Bitacora::registrar('editar', 'horario_atencion', $nuevoId);
            return $nuevoId;
        });
        return $this->redirigir('/horarios?medico_id=' . $medicoId, 'exito', 'Ventana actualizada desde el ' . fecha_corta($ventana['vigente_desde']) . ' (ventana #' . $nuevoId . ').');
    }

    /** Termina la vigencia de una ventana desde hoy; las citas ya reservadas se conservan. */
    public function quitarVentana(string $id): Respuesta
    {
        $horarios = new Ehorario_atencion();
        $ventana = $horarios->buscarPorId((int) $id) ?? throw new ExcepcionNoEncontrado('La ventana no existe.');
        $horarios->cerrarVigencia((int) $ventana['id'], date('Y-m-d'));
        Bitacora::registrar('desactivar', 'horario_atencion', (int) $ventana['id']);
        return $this->redirigir('/horarios?medico_id=' . $ventana['medico_id'], 'exito', 'La ventana dejó de ofrecerse desde hoy. Las citas ya reservadas se conservan.');
    }

    /** Flujo principal 6–8: bloquear un día; si tiene citas se listan y se pide confirmación. */
    public function bloquear(string $medico): Respuesta
    {
        $medico = $this->medicoActivo((int) $medico);
        $medicoId = (int) $medico['id'];
        $datos = $this->validar(['fecha' => 'requerido|fecha|fecha_no_pasada', 'motivo' => 'requerido|min:3|max:250']);
        $bloqueos = new Ebloqueo_agenda();
        if ($bloqueos->existe($medicoId, $datos['fecha'])) {
            throw new ExcepcionValidacion(['fecha' => 'Ya existe un bloqueo para este médico en esa fecha.'], $this->solicitud->todo());
        }

        $afectadas = (new Ecita_medica())->noCanceladasEnFecha($medicoId, $datos['fecha']);
        if ($afectadas && $this->solicitud->entrada('confirmar') !== '1') {
            Sesion::poner(self::BLOQUEO_PENDIENTE, ['medico_id' => $medicoId, 'fecha' => $datos['fecha'], 'motivo' => trim($datos['motivo'])]);
            return $this->redirigir('/horarios?medico_id=' . $medicoId);
        }

        $id = $bloqueos->insertar(['medico_id' => $medicoId, 'fecha' => $datos['fecha'], 'motivo' => trim($datos['motivo'])]);
        Bitacora::registrar('bloquear', 'bloqueo_agenda', $id);
        Sesion::quitar(self::BLOQUEO_PENDIENTE);
        if ($afectadas) {
            // A2: las citas afectadas no se cancelan solas; recepción las reprograma (CU10) o cancela (CU11).
            Sesion::flash('aviso', count($afectadas) . ' cita(s) de ese día deben reprogramarse o cancelarse: ' . implode(', ', array_column($afectadas, 'codigo')) . '.');
        }
        return $this->redirigir('/horarios?medico_id=' . $medicoId, 'exito', 'Día ' . fecha_corta($datos['fecha']) . ' bloqueado; sus cupos dejaron de ofrecerse.');
    }

    public function descartarBloqueo(): Respuesta
    {
        $pendiente = Sesion::obtener(self::BLOQUEO_PENDIENTE);
        Sesion::quitar(self::BLOQUEO_PENDIENTE);
        return $this->redirigir('/horarios' . ($pendiente ? '?medico_id=' . $pendiente['medico_id'] : ''), 'info', 'No se guardó el bloqueo.');
    }

    public function quitarBloqueo(string $id): Respuesta
    {
        $bloqueos = new Ebloqueo_agenda();
        $bloqueo = $bloqueos->buscarPorId((int) $id) ?? throw new ExcepcionNoEncontrado('El bloqueo no existe.');
        $bloqueos->eliminar((int) $bloqueo['id']);
        Bitacora::registrar('desbloquear', 'bloqueo_agenda', (int) $bloqueo['id']);
        return $this->redirigir('/horarios?medico_id=' . $bloqueo['medico_id'], 'exito', 'El día ' . fecha_corta($bloqueo['fecha']) . ' vuelve a estar disponible.');
    }

    private function medicoActivo(int $medicoId): array
    {
        $medico = (new Emedico())->buscarDetalle($medicoId) ?? throw new ExcepcionNoEncontrado('El médico no existe.');
        if (!$medico['activo']) {
            throw new ExcepcionNegocio('El médico está inactivo; reactívalo antes de configurar su agenda.');
        }
        return $medico;
    }

    /** Paso 4 y E1–E3: horas coherentes, rango divisible entre la duración, vigencia válida y sin superposición. */
    private function ventanaValidada(int $medicoId, ?int $excluirId = null): array
    {
        $datos = $this->validar(self::REGLAS_VENTANA);
        $inicio = substr($datos['hora_inicio'], 0, 5);
        $fin = substr($datos['hora_fin'], 0, 5);
        $duracion = (int) $datos['duracion_cita_min'];
        $hasta = ($datos['vigente_hasta'] ?? '') !== '' ? $datos['vigente_hasta'] : null;
        $minutos = intdiv(strtotime("2000-01-01 $fin") - strtotime("2000-01-01 $inicio"), 60);

        $errores = [];
        if ($minutos <= 0) {
            $errores['hora_fin'] = 'La hora de fin debe ser posterior a la hora de inicio.';
        } elseif ($minutos % $duracion !== 0) {
            $errores['hora_fin'] = "El rango ($minutos min) debe dividirse en turnos completos de $duracion min.";
        }
        if ($hasta !== null && $hasta < $datos['vigente_desde']) {
            $errores['vigente_hasta'] = 'La vigencia final no puede ser anterior a la inicial.';
        }
        if (!$errores && (new Ehorario_atencion())->seSuperpone($medicoId, (int) $datos['dia_semana'], $inicio, $fin, $datos['vigente_desde'], $hasta, $excluirId)) {
            $errores['hora_inicio'] = 'Se superpone con otra ventana del mismo médico ese día.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }
        return [
            'dia_semana'        => (int) $datos['dia_semana'],
            'hora_inicio'       => $inicio,
            'hora_fin'          => $fin,
            'duracion_cita_min' => $duracion,
            'vigente_desde'     => $datos['vigente_desde'],
            'vigente_hasta'     => $hasta,
        ];
    }
}
