<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Econtacto_emergencia;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Edatos_personales;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eperfil;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epreferencia_cuenta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eusuario;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vpaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epago;

/** CU04 · Gestionar pacientes. */
final class Cpaciente extends Controlador
{
    private const REGLAS_PERSONA = [
        'ci'               => 'requerido|max:30',
        'nombres'          => 'requerido|max:100',
        'apellidos'        => 'requerido|max:100',
        'fecha_nacimiento' => 'requerido|fecha|fecha_no_futura',
        'sexo'             => 'requerido|en:Femenino,Masculino,Otro',
        'telefono'         => 'requerido|telefono',
        'correo'           => 'opcional|correo|max:254',
        'direccion'        => 'opcional|max:250',
    ];

    public function listar(): Respuesta
    {
        $activo = (string) $this->solicitud->consulta('activo', '');
        $resultado = (new Epaciente())->listar(
            trim((string) $this->solicitud->consulta('buscar', '')),
            $activo === '' ? null : $activo === '1',
            (int) $this->solicitud->consulta('pagina', 1)
        );
        return (new Vpaciente())->mostrarLista($resultado);
    }

    public function mostrarNuevo(): Respuesta
    {
        return (new Vpaciente())->mostrarFormulario(null, []);
    }

    /** Flujo principal 3–10: registra persona, paciente, ficha inicial, contactos y cuenta opcional. */
    public function crear(): Respuesta
    {
        $crearCuenta = $this->solicitud->entrada('crear_cuenta') === '1';
        $datos = $this->validar(self::REGLAS_PERSONA + [
            'tipo_sangre'    => 'opcional|en:' . implode(',', Eficha_medica::TIPOS_SANGRE),
            'alergias'       => 'opcional|max:1000',
            'nombre_usuario' => $crearCuenta ? 'requerido|nombre_usuario' : 'opcional',
        ]);
        $persona = Edatos_personales::normalizar($datos);
        $contactos = $this->contactos();
        $personas = new Edatos_personales();
        $usuarios = new Eusuario();

        $errores = [];
        $existente = $personas->buscarPorCi($persona['ci']);
        if ($existente && $existente['es_paciente']) {
            $errores['ci'] = 'Ya existe un paciente con el CI indicado (ver la lista de pacientes).';
        }
        if (!empty($persona['correo']) && $personas->existeCorreo($persona['correo'], $existente['id'] ?? null)) {
            $errores['correo'] = 'Este correo ya está registrado por otra persona.';
        }
        if ($crearCuenta && $usuarios->existeNombre($datos['nombre_usuario'])) {
            $errores['nombre_usuario'] = 'Este nombre de usuario ya está en uso.';
        }
        if ($crearCuenta && $existente && $usuarios->personaTieneCuenta((int) $existente['id'])) {
            $errores['nombre_usuario'] = 'Esta persona ya tiene una cuenta de acceso.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        $temporal = $crearCuenta ? 'Vs' . random_int(100000, 999999) : null;
        $operadorId = (int) $this->usuario()['id'];
        [$pacienteId, $codigo] = BaseDatos::transaccion(function () use ($existente, $persona, $datos, $contactos, $personas, $usuarios, $temporal, $operadorId): array {
            // Una persona ya registrada que no es paciente (p. ej. un médico) conserva su identidad única.
            if ($existente) {
                $personaId = (int) $existente['id'];
                $personas->actualizar($personaId, $persona);
            } else {
                $personaId = $personas->insertar($persona);
            }
            $pacientes = new Epaciente();
            $codigo = $pacientes->siguienteCodigo();
            $pacientes->registrar($personaId, $codigo);
            (new Eficha_medica())->crear($personaId, $operadorId, $datos['tipo_sangre'] ?? null, trim((string) ($datos['alergias'] ?? '')));
            (new Econtacto_emergencia())->reemplazar($personaId, $contactos);
            if ($temporal) {
                $usuarioId = $usuarios->registrar($personaId, Eperfil::PACIENTE, $datos['nombre_usuario'], $temporal);
                (new Epreferencia_cuenta())->guardar($usuarioId, true, false);
            }
            Bitacora::registrar('registrar', 'paciente', $personaId);
            return [$personaId, $codigo];
        });

        $mensaje = "Paciente $codigo registrado correctamente.";
        if ($temporal) {
            $mensaje .= ' Cuenta «' . $datos['nombre_usuario'] . "» creada con la contraseña temporal $temporal.";
        }
        return $this->redirigir('/pacientes/' . $pacienteId, 'exito', $mensaje);
    }

    public function mostrar(string $id): Respuesta
    {
        $paciente = $this->buscar($id);
        $pacienteId = (int) $paciente['id'];
        return (new Vpaciente())->mostrarFicha(
            $paciente,
            (new Econtacto_emergencia())->dePaciente($pacienteId),
            (new Eficha_medica())->dePaciente($pacienteId),
            (new Ecita_medica())->dePaciente($pacienteId),
            (new Epago())->dePaciente($pacienteId)
        );
    }

    public function mostrarEditar(string $id): Respuesta
    {
        $paciente = $this->buscar($id);
        $paciente['contactos'] = (new Econtacto_emergencia())->dePaciente((int) $paciente['id']);
        return (new Vpaciente())->mostrarFormulario($paciente, []);
    }

    public function actualizar(string $id): Respuesta
    {
        $paciente = $this->buscar($id);
        $pacienteId = (int) $paciente['id'];
        $persona = Edatos_personales::normalizar($this->validar(self::REGLAS_PERSONA));
        $contactos = $this->contactos();
        $personas = new Edatos_personales();

        $errores = [];
        $otro = $personas->buscarPorCi($persona['ci']);
        if ($otro && (int) $otro['id'] !== $pacienteId) {
            $errores['ci'] = 'Ese CI pertenece a otra persona registrada.';
        }
        if (!empty($persona['correo']) && $personas->existeCorreo($persona['correo'], $pacienteId)) {
            $errores['correo'] = 'Este correo ya está registrado por otra persona.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        BaseDatos::transaccion(function () use ($pacienteId, $persona, $contactos, $personas): void {
            $personas->actualizar($pacienteId, $persona);
            (new Econtacto_emergencia())->reemplazar($pacienteId, $contactos);
            Bitacora::registrar('editar', 'paciente', $pacienteId);
        });
        return $this->redirigir('/pacientes/' . $pacienteId, 'exito', 'Datos del paciente actualizados.');
    }

    /** A2: el paciente inactivo no recibe nuevas citas, pero conserva su historial (RN11). */
    public function desactivar(string $id): Respuesta
    {
        return $this->cambiarEstado($id, false);
    }

    public function activar(string $id): Respuesta
    {
        return $this->cambiarEstado($id, true);
    }

    private function cambiarEstado(string $id, bool $activo): Respuesta
    {
        $paciente = $this->buscar($id);
        (new Epaciente())->cambiarEstado((int) $paciente['id'], $activo);
        Bitacora::registrar($activo ? 'activar' : 'desactivar', 'paciente', (int) $paciente['id']);
        return $this->redirigir('/pacientes/' . $paciente['id'], 'exito', 'Paciente ' . $paciente['codigo'] . ($activo ? ' reactivado.' : ' desactivado; su historial se conserva.'));
    }

    private function buscar(string $id): array
    {
        return (new Epaciente())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('El paciente no existe.');
    }

    /** Filas de contactos de emergencia (contactos[i][nombre|parentesco|telefono]); ignora las vacías. */
    private function contactos(): array
    {
        $contactos = [];
        $errores = [];
        foreach ((array) $this->solicitud->entrada('contactos', []) as $i => $fila) {
            $fila = array_map(static fn ($valor): string => trim((string) $valor), (array) $fila + ['nombre' => '', 'parentesco' => '', 'telefono' => '']);
            if ($fila['nombre'] === '' && $fila['parentesco'] === '' && $fila['telefono'] === '') {
                continue;
            }
            foreach (Validador::validar($fila, ['nombre' => 'requerido|max:150', 'parentesco' => 'requerido|max:50', 'telefono' => 'requerido|telefono']) as $campo => $mensaje) {
                $errores["contactos[$i][$campo]"] = $mensaje;
            }
            $contactos[] = $fila;
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }
        return $contactos;
    }
}
