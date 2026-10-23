<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Sesion;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Edatos_personales;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eperfil;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epreferencia_cuenta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eusuario;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vacceso;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vcuenta;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;

/** CU01 · Gestionar acceso y cuenta personal. */
final class Cacceso extends Controlador
{
    public function mostrarLogin(): Respuesta
    {
        if ($this->usuario()) {
            return $this->redirigir('/panel');
        }
        return (new Vacceso())->mostrarLogin();
    }

    /** Flujo principal 2–5: valida credenciales, comprueba la cuenta e inicia la sesión. */
    public function iniciarSesion(): Respuesta
    {
        $datos = $this->validar(['usuario_o_correo' => 'requerido|max:254', 'contrasena' => 'requerido|max:200']);
        $usuarios = new Eusuario();
        $usuario = $usuarios->buscarParaAcceso(trim($datos['usuario_o_correo']));

        if (!$usuario || !password_verify($datos['contrasena'], $usuario['contrasena_hash'])) {
            if ($usuario) {
                Bitacora::registrar('acceso_fallido', 'usuario', (int) $usuario['id'], (int) $usuario['id']);
            }
            throw new ExcepcionNegocio('Usuario o contraseña incorrectos.');
        }
        if (!$usuario['activo']) {
            throw new ExcepcionNegocio('Tu cuenta está inactiva. Comunícate con la clínica.');
        }
        if (password_needs_rehash($usuario['contrasena_hash'], PASSWORD_DEFAULT)) {
            $usuarios->cambiarContrasena((int) $usuario['id'], $datos['contrasena']);
        }

        $this->abrirSesion($usuario);
        Bitacora::registrar('iniciar_sesion', 'usuario', (int) $usuario['id']);

        $destino = (string) Sesion::obtener('_destino', '/panel');
        Sesion::quitar('_destino');
        return $this->redirigir($destino);
    }

    public function cerrarSesion(): Respuesta
    {
        $usuario = $this->usuario();
        if ($usuario) {
            Bitacora::registrar('cerrar_sesion', 'usuario', (int) $usuario['id']);
        }
        Sesion::cerrar();
        Sesion::iniciar($this->solicitud->esSegura());
        return $this->redirigir('/login', 'exito', 'Cerraste sesión correctamente.');
    }

    public function mostrarRegistro(): Respuesta
    {
        if ($this->usuario()) {
            return $this->redirigir('/panel');
        }
        return (new Vacceso())->mostrarRegistro();
    }

    /** Flujo alternativo A1: registro web del paciente con su cuenta. */
    public function registrar(): Respuesta
    {
        $datos = $this->validar([
            'ci'                      => 'requerido|max:30',
            'fecha_nacimiento'        => 'requerido|fecha|fecha_no_futura',
            'nombres'                 => 'requerido|max:100',
            'apellidos'               => 'requerido|max:100',
            'sexo'                    => 'requerido|en:Femenino,Masculino,Otro',
            'telefono'                => 'requerido|telefono',
            'correo'                  => 'requerido|correo|max:254',
            'nombre_usuario'          => 'requerido|nombre_usuario',
            'contrasena'              => 'requerido|contrasena',
            'contrasena_confirmacion' => 'requerido|coincide:contrasena',
            'aceptar_terminos'        => 'requerido',
        ]);
        $persona = Edatos_personales::normalizar($datos);
        $personas = new Edatos_personales();
        $usuarios = new Eusuario();

        if ($existente = $personas->buscarPorCi($persona['ci'])) {
            // A2: no se crea una segunda identidad; la cuenta se solicita en recepción (CU04).
            throw new ExcepcionNegocio($existente['es_paciente']
                ? 'Ya estás registrado como paciente. Solicita la creación de tu cuenta en recepción.'
                : 'Ya existe una persona registrada con ese CI. Acércate a recepción.');
        }
        $errores = [];
        if ($personas->existeCorreo($persona['correo'])) {
            $errores['correo'] = 'Este correo ya está registrado.';
        }
        if ($usuarios->existeNombre($datos['nombre_usuario'])) {
            $errores['nombre_usuario'] = 'Este nombre de usuario ya está en uso.';
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        $usuarioId = BaseDatos::transaccion(function () use ($persona, $datos, $personas, $usuarios): int {
            $personaId = $personas->insertar($persona);
            $pacientes = new Epaciente();
            $pacientes->registrar($personaId, $pacientes->siguienteCodigo());
            $usuarioId = $usuarios->registrar($personaId, Eperfil::PACIENTE, $datos['nombre_usuario'], $datos['contrasena']);
            (new Eficha_medica())->crear($personaId, $usuarioId);
            (new Epreferencia_cuenta())->guardar($usuarioId, true, false);
            Bitacora::registrar('registrar', 'paciente', $personaId, $usuarioId);
            return $usuarioId;
        });

        $this->abrirSesion($usuarios->buscarDetalle($usuarioId));
        return $this->redirigir('/panel', 'exito', 'Tu cuenta fue creada. Ya puedes reservar tu primera cita.');
    }

    public function mostrarCuenta(): Respuesta
    {
        $usuario = (new Eusuario())->buscarDetalle((int) $this->usuario()['id']);
        $persona = (new Edatos_personales())->buscarPorId((int) $usuario['datos_personales_id']);
        $preferencias = (new Epreferencia_cuenta())->deUsuario((int) $usuario['id']);
        $pestana = (string) $this->solicitud->consulta('pestana', 'datos');
        return (new Vcuenta())->mostrar($usuario, $persona, $preferencias, $pestana);
    }

    public function cambiarContrasena(): Respuesta
    {
        $datos = $this->validar([
            'contrasena_actual'       => 'requerido',
            'contrasena_nueva'        => 'requerido|contrasena',
            'contrasena_confirmacion' => 'requerido|coincide:contrasena_nueva',
        ]);
        $usuarios = new Eusuario();
        $usuario = $usuarios->buscarDetalle((int) $this->usuario()['id']);
        if (!password_verify($datos['contrasena_actual'], $usuario['contrasena_hash'])) {
            throw new ExcepcionValidacion(['contrasena_actual' => 'La contraseña actual no es correcta.']);
        }
        $usuarios->cambiarContrasena((int) $usuario['id'], $datos['contrasena_nueva']);
        Bitacora::registrar('cambiar_contrasena', 'usuario', (int) $usuario['id']);
        return $this->redirigir('/mi-cuenta?pestana=contrasena', 'exito', 'Tu contraseña se actualizó correctamente.');
    }

    public function guardarPreferencias(): Respuesta
    {
        $usuarioId = (int) $this->usuario()['id'];
        (new Epreferencia_cuenta())->guardar(
            $usuarioId,
            $this->solicitud->entrada('notificaciones') === '1',
            $this->solicitud->entrada('mostrar_contacto_al_medico') === '1'
        );
        Bitacora::registrar('editar', 'preferencia_cuenta', $usuarioId);
        return $this->redirigir('/mi-cuenta?pestana=preferencias', 'exito', 'Preferencias guardadas.');
    }

    /** Datos mínimos que se guardan en la sesión (sin el hash de la contraseña). */
    private function abrirSesion(array $usuario): void
    {
        Sesion::iniciarUsuario([
            'id'                  => (int) $usuario['id'],
            'perfil_id'           => (int) $usuario['perfil_id'],
            'perfil'              => $usuario['perfil'],
            'datos_personales_id' => (int) $usuario['datos_personales_id'],
            'nombres'             => $usuario['nombres'],
            'apellidos'           => $usuario['apellidos'],
            'nombre_usuario'      => $usuario['nombre_usuario'],
            'codigo_medico'       => $usuario['codigo_medico'],
        ]);
    }
}
