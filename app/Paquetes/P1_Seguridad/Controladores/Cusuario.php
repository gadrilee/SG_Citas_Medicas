<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Sesion;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Edatos_personales;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eperfil;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eperfil_privilegio;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epersonal;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epreferencia_cuenta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eprivilegio;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Eusuario;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vperfil;
use VidaSalud\Paquetes\P1_Seguridad\Vistas\Vusuario;

/** CU02 · Administrar usuarios y permisos. */
final class Cusuario extends Controlador
{
    /** Privilegios que el perfil administrador conserva siempre (evita quedar sin acceso). */
    private const PRIVILEGIOS_FIJOS_ADMIN = ['usuarios.gestionar', 'perfiles.gestionar'];

    public function listar(): Respuesta
    {
        $usuarios = new Eusuario();
        $activo = $this->solicitud->consulta('activo', '');
        $resultado = $usuarios->listar(
            trim((string) $this->solicitud->consulta('buscar', '')),
            (int) $this->solicitud->consulta('perfil_id') ?: null,
            $activo === '' ? null : $activo === '1',
            (int) $this->solicitud->consulta('pagina', 1)
        );
        $editarId = (int) $this->solicitud->consulta('editar');
        $editar = $editarId ? $usuarios->buscarDetalle($editarId) : null;

        return (new Vusuario())->mostrarLista($resultado, $usuarios->personasSinCuenta(), $editar, (int) $this->usuario()['id']);
    }

    /** Flujo principal 3–5: cuenta para una persona existente o para personal nuevo. */
    public function crear(): Respuesta
    {
        $nueva = $this->solicitud->entrada('origen') === 'nueva';
        $reglas = [
            'nombre_usuario' => 'requerido|nombre_usuario',
            'contrasena'     => 'requerido|contrasena',
            'perfil_id'      => 'requerido|en:1,2,3,4',
        ];
        $reglas += $nueva ? [
            'ci'               => 'requerido|max:30',
            'nombres'          => 'requerido|max:100',
            'apellidos'        => 'requerido|max:100',
            'fecha_nacimiento' => 'requerido|fecha|fecha_no_futura',
            'telefono'         => 'requerido|telefono',
            'correo'           => 'opcional|correo|max:254',
            'fecha_ingreso'    => 'requerido|fecha|fecha_no_futura',
        ] : ['persona_id' => 'requerido|entero'];
        $datos = $this->validar($reglas);
        $perfilId = (int) $datos['perfil_id'];
        $usuarios = new Eusuario();
        $personas = new Edatos_personales();

        $errores = [];
        if ($usuarios->existeNombre($datos['nombre_usuario'])) {
            $errores['nombre_usuario'] = 'Este nombre de usuario ya está en uso.';
        }
        if ($nueva) {
            $persona = Edatos_personales::normalizar($datos);
            if (!in_array($perfilId, [Eperfil::ADMINISTRADOR, Eperfil::RECEPCIONISTA], true)) {
                $errores['perfil_id'] = 'El personal nuevo solo puede ser administrador o recepcionista.';
            }
            if ($personas->buscarPorCi($persona['ci'])) {
                $errores['ci'] = 'Ya existe una persona con este CI; elígela en «Persona existente».';
            }
            if (!empty($persona['correo']) && $personas->existeCorreo($persona['correo'])) {
                $errores['correo'] = 'Este correo ya está registrado.';
            }
        } else {
            $persona = $personas->buscarConTipos((int) $datos['persona_id']);
            if (!$persona) {
                $errores['persona_id'] = 'Selecciona una persona válida.';
            } elseif ($usuarios->personaTieneCuenta((int) $persona['id'])) {
                $errores['persona_id'] = 'Esta persona ya tiene una cuenta.';
            } elseif ($mensaje = self::perfilIncompatible($perfilId, $persona)) {
                $errores['perfil_id'] = $mensaje;
            }
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        $usuarioId = BaseDatos::transaccion(function () use ($nueva, $persona, $datos, $perfilId, $usuarios, $personas): int {
            $personaId = $nueva ? $personas->insertar($persona) : (int) $persona['id'];
            if ($nueva) {
                (new Epersonal())->registrar($personaId, $datos['fecha_ingreso']);
            } elseif (in_array($perfilId, [Eperfil::ADMINISTRADOR, Eperfil::RECEPCIONISTA], true) && !$persona['es_personal']) {
                (new Epersonal())->registrar($personaId, date('Y-m-d'));
            }
            $usuarioId = $usuarios->registrar($personaId, $perfilId, $datos['nombre_usuario'], $datos['contrasena']);
            (new Epreferencia_cuenta())->guardar($usuarioId, true, false);
            Bitacora::registrar('crear', 'usuario', $usuarioId);
            return $usuarioId;
        });

        return $this->redirigir('/usuarios', 'exito', 'Cuenta «' . $datos['nombre_usuario'] . '» creada (usuario #' . $usuarioId . ').');
    }

    public function actualizar(string $id): Respuesta
    {
        $usuarios = new Eusuario();
        $usuario = $this->buscar($usuarios, $id);
        $datos = $this->validar(['nombre_usuario' => 'requerido|nombre_usuario', 'perfil_id' => 'requerido|en:1,2,3,4']);
        $perfilId = (int) $datos['perfil_id'];

        $errores = [];
        if ($usuarios->existeNombre($datos['nombre_usuario'], (int) $usuario['id'])) {
            $errores['nombre_usuario'] = 'Este nombre de usuario ya está en uso.';
        }
        if ($perfilId !== (int) $usuario['perfil_id']) {
            if ((int) $usuario['id'] === (int) $this->usuario()['id']) {
                $errores['perfil_id'] = 'No puedes cambiar el perfil de tu propia cuenta.';
            } elseif ($mensaje = self::perfilIncompatible($perfilId, $usuario)) {
                $errores['perfil_id'] = $mensaje;
            } elseif ((int) $usuario['perfil_id'] === Eperfil::ADMINISTRADOR && $usuario['activo'] && $usuarios->contarAdministradoresActivos() <= 1) {
                $errores['perfil_id'] = 'Debe quedar al menos un administrador activo.';
            }
        }
        if ($errores) {
            throw new ExcepcionValidacion($errores, $this->solicitud->todo());
        }

        BaseDatos::transaccion(function () use ($usuarios, $usuario, $datos, $perfilId): void {
            if (in_array($perfilId, [Eperfil::ADMINISTRADOR, Eperfil::RECEPCIONISTA], true) && !$usuario['es_personal']) {
                (new Epersonal())->registrar((int) $usuario['datos_personales_id'], date('Y-m-d'));
            }
            $usuarios->actualizar((int) $usuario['id'], ['nombre_usuario' => mb_strtolower($datos['nombre_usuario']), 'perfil_id' => $perfilId]);
            Bitacora::registrar('editar', 'usuario', (int) $usuario['id']);
        });
        return $this->redirigir('/usuarios', 'exito', 'Cuenta «' . $datos['nombre_usuario'] . '» actualizada.');
    }

    /** Genera una contraseña temporal; el administrador la entrega a la persona. */
    public function restablecer(string $id): Respuesta
    {
        $usuarios = new Eusuario();
        $usuario = $this->buscar($usuarios, $id);
        $temporal = 'Vs' . random_int(100000, 999999);
        $usuarios->cambiarContrasena((int) $usuario['id'], $temporal);
        Bitacora::registrar('restablecer_contrasena', 'usuario', (int) $usuario['id']);
        return $this->redirigir('/usuarios', 'exito', 'Contraseña temporal de «' . $usuario['nombre_usuario'] . '»: ' . $temporal);
    }

    public function desactivar(string $id): Respuesta
    {
        $usuarios = new Eusuario();
        $usuario = $this->buscar($usuarios, $id);
        if ((int) $usuario['id'] === (int) $this->usuario()['id']) {
            throw new ExcepcionNegocio('No puedes desactivar tu propia cuenta.');
        }
        if ((int) $usuario['perfil_id'] === Eperfil::ADMINISTRADOR && $usuarios->contarAdministradoresActivos() <= 1) {
            throw new ExcepcionNegocio('Debe quedar al menos un administrador activo.');
        }
        $usuarios->cambiarEstado((int) $usuario['id'], false);
        Bitacora::registrar('desactivar', 'usuario', (int) $usuario['id']);
        return $this->redirigir('/usuarios', 'exito', 'Cuenta «' . $usuario['nombre_usuario'] . '» desactivada.');
    }

    public function activar(string $id): Respuesta
    {
        $usuarios = new Eusuario();
        $usuario = $this->buscar($usuarios, $id);
        $usuarios->cambiarEstado((int) $usuario['id'], true);
        Bitacora::registrar('activar', 'usuario', (int) $usuario['id']);
        return $this->redirigir('/usuarios', 'exito', 'Cuenta «' . $usuario['nombre_usuario'] . '» reactivada.');
    }

    public function mostrarPerfiles(): Respuesta
    {
        $perfiles = (new Eperfil())->listarConConteo();
        $perfilId = (int) $this->solicitud->consulta('perfil', Eperfil::ADMINISTRADOR);
        $perfil = current(array_filter($perfiles, static fn (array $p): bool => (int) $p['id'] === $perfilId)) ?: $perfiles[0];
        $privilegios = (new Eprivilegio())->listarParaPerfil((int) $perfil['id']);
        return (new Vperfil())->mostrar($perfiles, $perfil, $privilegios, self::PRIVILEGIOS_FIJOS_ADMIN);
    }

    public function guardarPrivilegios(string $id): Respuesta
    {
        $perfilId = (int) $id;
        if ($perfilId < 1 || $perfilId > 4) {
            throw new ExcepcionNoEncontrado('El perfil no existe.');
        }
        $privilegios = new Eprivilegio();
        $catalogo = $privilegios->listarParaPerfil($perfilId);
        $marcados = array_map('intval', (array) $this->solicitud->entrada('privilegios', []));
        $validos = array_column($catalogo, 'id');
        $seleccion = array_values(array_intersect($validos, $marcados));
        if ($perfilId === Eperfil::ADMINISTRADOR) {
            foreach ($catalogo as $privilegio) {
                if (in_array($privilegio['nombre'], self::PRIVILEGIOS_FIJOS_ADMIN, true)) {
                    $seleccion[] = (int) $privilegio['id'];
                }
            }
        }

        BaseDatos::transaccion(function () use ($perfilId, $seleccion): void {
            (new Eperfil_privilegio())->asignar($perfilId, array_unique($seleccion));
            Bitacora::registrar('editar_privilegios', 'perfil', $perfilId);
        });
        if ((int) $this->usuario()['perfil_id'] === $perfilId) {
            Sesion::quitar('_privilegios');
        }
        return $this->redirigir('/perfiles?perfil=' . $perfilId, 'exito', 'Privilegios actualizados. Los demás usuarios los verán al volver a iniciar sesión.');
    }

    private function buscar(Eusuario $usuarios, string $id): array
    {
        return $usuarios->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('La cuenta no existe.');
    }

    /** El perfil debe corresponder al tipo de persona: médico ↔ médico, paciente ↔ paciente. */
    private static function perfilIncompatible(int $perfilId, array $persona): ?string
    {
        return match (true) {
            $perfilId === Eperfil::MEDICO && !$persona['es_medico']     => 'Solo una persona registrada como médico (CU05) puede tener el perfil médico.',
            $perfilId === Eperfil::PACIENTE && !$persona['es_paciente'] => 'Solo una persona registrada como paciente (CU04) puede tener el perfil paciente.',
            default                                                     => null,
        };
    }
}
