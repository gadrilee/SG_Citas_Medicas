<?php

declare(strict_types=1);

use VidaSalud\Paquetes\P1_Seguridad\Controladores\Cacceso;
use VidaSalud\Paquetes\P1_Seguridad\Controladores\Cbitacora;
use VidaSalud\Paquetes\P1_Seguridad\Controladores\Cmedico;
use VidaSalud\Paquetes\P1_Seguridad\Controladores\Cpaciente;
use VidaSalud\Paquetes\P1_Seguridad\Controladores\Cusuario;

// P1 Gestión de seguridad y personas (CU01–CU05).
// Formato: [método, ruta, [Controlador::class, 'método'], privilegio, opciones]
// Las rutas fijas (/pacientes/nuevo) van antes que las variables (/pacientes/{id}).
return [
    // CU01 · Gestionar acceso y cuenta personal
    ['GET', '/login', [Cacceso::class, 'mostrarLogin'], null],
    ['POST', '/login', [Cacceso::class, 'iniciarSesion'], null],
    ['POST', '/logout', [Cacceso::class, 'cerrarSesion'], 'autenticado'],
    ['GET', '/registro', [Cacceso::class, 'mostrarRegistro'], null],
    ['POST', '/registro', [Cacceso::class, 'registrar'], null],
    ['GET', '/mi-cuenta', [Cacceso::class, 'mostrarCuenta'], 'autenticado'],
    ['POST', '/mi-cuenta/contrasena', [Cacceso::class, 'cambiarContrasena'], 'autenticado'],
    ['POST', '/mi-cuenta/preferencias', [Cacceso::class, 'guardarPreferencias'], 'autenticado'],

    // CU02 · Administrar usuarios y permisos
    ['GET', '/usuarios', [Cusuario::class, 'listar'], 'usuarios.gestionar'],
    ['POST', '/usuarios', [Cusuario::class, 'crear'], 'usuarios.gestionar'],
    ['PUT', '/usuarios/{id}', [Cusuario::class, 'actualizar'], 'usuarios.gestionar'],
    ['POST', '/usuarios/{id}/restablecer', [Cusuario::class, 'restablecer'], 'usuarios.gestionar'],
    ['POST', '/usuarios/{id}/desactivar', [Cusuario::class, 'desactivar'], 'usuarios.gestionar'],
    ['POST', '/usuarios/{id}/activar', [Cusuario::class, 'activar'], 'usuarios.gestionar'],
    ['GET', '/perfiles', [Cusuario::class, 'mostrarPerfiles'], 'perfiles.gestionar'],
    ['PUT', '/perfiles/{id}/privilegios', [Cusuario::class, 'guardarPrivilegios'], 'perfiles.gestionar'],

    // CU03 · Consultar bitácora
    ['GET', '/bitacora', [Cbitacora::class, 'listar'], 'bitacora.consultar'],

    // CU04 · Gestionar pacientes
    ['GET', '/pacientes', [Cpaciente::class, 'listar'], 'pacientes.gestionar'],
    ['GET', '/pacientes/nuevo', [Cpaciente::class, 'mostrarNuevo'], 'pacientes.gestionar'],
    ['POST', '/pacientes', [Cpaciente::class, 'crear'], 'pacientes.gestionar'],
    ['GET', '/pacientes/{id}', [Cpaciente::class, 'mostrar'], 'pacientes.gestionar'],
    ['GET', '/pacientes/{id}/editar', [Cpaciente::class, 'mostrarEditar'], 'pacientes.gestionar'],
    ['PUT', '/pacientes/{id}', [Cpaciente::class, 'actualizar'], 'pacientes.gestionar'],
    ['POST', '/pacientes/{id}/desactivar', [Cpaciente::class, 'desactivar'], 'pacientes.gestionar'],
    ['POST', '/pacientes/{id}/activar', [Cpaciente::class, 'activar'], 'pacientes.gestionar'],

    // CU05 · Gestionar médicos
    ['GET', '/medicos', [Cmedico::class, 'listar'], 'medicos.gestionar'],
    ['GET', '/medicos/nuevo', [Cmedico::class, 'mostrarNuevo'], 'medicos.gestionar'],
    ['POST', '/medicos', [Cmedico::class, 'crear'], 'medicos.gestionar'],
    ['GET', '/medicos/{id}/editar', [Cmedico::class, 'mostrarEditar'], 'medicos.gestionar'],
    ['PUT', '/medicos/{id}', [Cmedico::class, 'actualizar'], 'medicos.gestionar'],
    ['POST', '/medicos/{id}/desactivar', [Cmedico::class, 'desactivar'], 'medicos.gestionar'],
    ['POST', '/medicos/{id}/activar', [Cmedico::class, 'activar'], 'medicos.gestionar'],
];
