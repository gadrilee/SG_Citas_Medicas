<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Sesion;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Edatos_personales;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vmedico;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eespecialidad;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Emedico_especialidad;

/** CU05 · Gestionar médicos. */
final class Cmedico extends Controlador
{
    private const REGLAS = [
        'ci'                       => 'requerido|max:30',
        'nombres'                  => 'requerido|max:100',
        'apellidos'                => 'requerido|max:100',
        'fecha_nacimiento'         => 'requerido|fecha|fecha_no_futura',
        'sexo'                     => 'requerido|en:Femenino,Masculino,Otro',
        'telefono'                 => 'requerido|telefono',
        'correo'                   => 'opcional|correo|max:254',
        'nro_registro_profesional' => 'requerido|max:50',
        'especialidades'           => 'requerido|arreglo',
    ];

    public function listar(): Respuesta
    {
        $especialidades = (new Eespecialidad())->activas();
        $medicos = (new Emedico())->listar(
            trim((string) $this->solicitud->consulta('buscar', '')),
            (int) $this->solicitud->consulta('especialidad_id') ?: null
        );
        $desactivarId = (int) $this->solicitud->consulta('desactivar');
        $afectadas = $desactivarId ? (new Ecita_medica())->futurasDeMedico($desactivarId) : [];
        return (new Vmedico())->mostrarLista($medicos, $especialidades, $desactivarId, $afectadas);
    }

    public function mostrarNuevo(): Respuesta
    {
        return (new Vmedico())->mostrarFormulario(null, (new Eespecialidad())->activas(), []);
    }

    /** Flujo principal 3–9 y A1 (la persona ya registrada reutiliza sus datos personales). */
    public function crear(): Respuesta
    {
        [$persona, $registro, $especialidadIds] = $this->datos();
        $personas = new Edatos_personales();
        $medicos = new Emedico();

        $errores = [];
        $existente = $personas->buscarPorCi($persona['ci']);
        if ($existente && $existente['es_medico']) {
            $errores['ci'] = 'Ya existe un médico con este CI.';
        }
        if (!empty($persona['correo']) && $personas->existeCorreo($persona['correo'], $existente['id'] ?? null)) {
            $errores['correo'] = 'Este correo ya está registrado por otra persona.';
        }
        if ($medicos->existeRegistro($registro)) {
            $errores['nro_registro_profesional'] = 'Este registro profesional ya pertenece a otro médico.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        [$medicoId, $codigo] = BaseDatos::transaccion(function () use ($existente, $persona, $registro, $especialidadIds, $personas, $medicos): array {
            if ($existente) {
                $medicoId = (int) $existente['id'];
                $personas->actualizar($medicoId, $persona);
            } else {
                $medicoId = $personas->insertar($persona);
            }
            $codigo = $medicos->siguienteCodigo();
            $medicos->registrar($medicoId, $codigo, $registro);
            (new Emedico_especialidad())->asignar($medicoId, $especialidadIds);
            Bitacora::registrar('registrar', 'medico', $medicoId);
            return [$medicoId, $codigo];
        });

        return $this->redirigir('/medicos', 'exito', "Médico $codigo registrado. Ya puedes configurar sus horarios (CU07) y crear su cuenta (CU02).");
    }

    public function mostrarEditar(string $id): Respuesta
    {
        $medico = $this->buscar($id);
        $medico['especialidades'] = (new Emedico_especialidad())->idsActivosDe((int) $medico['id']);
        return (new Vmedico())->mostrarFormulario($medico, (new Eespecialidad())->activas(), []);
    }

    public function actualizar(string $id): Respuesta
    {
        $medico = $this->buscar($id);
        $medicoId = (int) $medico['id'];
        [$persona, $registro, $especialidadIds] = $this->datos();
        $personas = new Edatos_personales();
        $medicos = new Emedico();

        $errores = [];
        $otro = $personas->buscarPorCi($persona['ci']);
        if ($otro && (int) $otro['id'] !== $medicoId) {
            $errores['ci'] = 'Ese CI pertenece a otra persona registrada.';
        }
        if (!empty($persona['correo']) && $personas->existeCorreo($persona['correo'], $medicoId)) {
            $errores['correo'] = 'Este correo ya está registrado por otra persona.';
        }
        if ($medicos->existeRegistro($registro, $medicoId)) {
            $errores['nro_registro_profesional'] = 'Este registro profesional ya pertenece a otro médico.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        BaseDatos::transaccion(function () use ($medicoId, $persona, $registro, $especialidadIds, $personas, $medicos): void {
            $personas->actualizar($medicoId, $persona);
            $medicos->actualizarRegistro($medicoId, $registro);
            (new Emedico_especialidad())->asignar($medicoId, $especialidadIds);
            Bitacora::registrar('editar', 'medico', $medicoId);
        });
        return $this->redirigir('/medicos', 'exito', 'Datos de ' . $medico['codigo'] . ' actualizados.');
    }

    /** A2: el médico inactivo no recibe nuevas citas; sus citas futuras quedan listadas para recepción. */
    public function desactivar(string $id): Respuesta
    {
        $medico = $this->buscar($id);
        (new Emedico())->cambiarEstado((int) $medico['id'], false);
        Bitacora::registrar('desactivar', 'medico', (int) $medico['id']);
        $afectadas = (new Ecita_medica())->futurasDeMedico((int) $medico['id']);
        if ($afectadas) {
            Sesion::flash('aviso', count($afectadas) . ' cita(s) futuras deben reprogramarse o cancelarse: ' . implode(', ', array_column($afectadas, 'codigo')) . '.');
        }
        return $this->redirigir('/medicos', 'exito', 'Médico ' . $medico['codigo'] . ' desactivado; sus citas históricas se conservan.');
    }

    public function activar(string $id): Respuesta
    {
        $medico = $this->buscar($id);
        (new Emedico())->cambiarEstado((int) $medico['id'], true);
        Bitacora::registrar('activar', 'medico', (int) $medico['id']);
        return $this->redirigir('/medicos', 'exito', 'Médico ' . $medico['codigo'] . ' reactivado.');
    }

    private function buscar(string $id): array
    {
        return (new Emedico())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('El médico no existe.');
    }

    /** @return array{0: array, 1: string, 2: int[]} datos personales, registro profesional y especialidades */
    private function datos(): array
    {
        $datos = $this->validar(self::REGLAS);
        $especialidadIds = (new Eespecialidad())->idsActivos((array) $datos['especialidades']);
        if (!$especialidadIds) {
            throw new ExcepcionValidacion(['especialidades' => 'Selecciona al menos una especialidad activa.'], $this->solicitud->todo());
        }
        return [Edatos_personales::normalizar($datos), mb_strtoupper(trim($datos['nro_registro_profesional'])), $especialidadIds];
    }
}
