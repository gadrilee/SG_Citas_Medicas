<?php

declare(strict_types=1);

namespace VidaSalud\Compartido\Controladores;

use Throwable;
use VidaSalud\Compartido\Vistas\Vinicio;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Configuracion;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Ebitacora;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eespecialidad;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epago;

/** Portada pública y panel de inicio según el perfil (no son casos de uso). */
final class Cinicio extends Controlador
{
    public function inicio(): Respuesta
    {
        $especialidades = $medicos = [];
        try {
            $especialidades = (new Eespecialidad())->activas();
            $medicos = (new Emedico())->activosConEspecialidades();
        } catch (Throwable) {
            // La portada se muestra aunque la base de datos todavía no esté disponible.
        }
        return (new Vinicio())->mostrarInicio($especialidades, $medicos);
    }

    public function panel(): Respuesta
    {
        $usuario = $this->usuario();
        $citas = new Ecita_medica();
        $datos = match ($usuario['perfil']) {
            'administrador' => [
                'indicadores' => $citas->indicadores() + [
                    'pacientes' => (new Epaciente())->contarActivos(),
                    'medicos'   => (new Emedico())->contarActivos(),
                ],
                'acciones'    => (new Ebitacora())->ultimas(6),
            ],
            'recepcionista' => [
                'indicadores' => $citas->indicadores() + ['pagos_pendientes' => (new Epago())->contarPendientes()],
                'proximas'    => $citas->proximas(null, null, 8),
            ],
            'medico' => [
                'indicadores' => $citas->indicadores((int) $usuario['datos_personales_id']),
                'proximas'    => $citas->proximas((int) $usuario['datos_personales_id'], null, 8),
            ],
            default => [
                'proximas' => $citas->proximas(null, (int) $usuario['datos_personales_id'], 6),
                'medicos'  => (new Emedico())->activosConEspecialidades(),
                'especialidades' => (new Eespecialidad())->activas(),
            ],
        };
        return (new Vinicio())->mostrarPanel($usuario, $datos);
    }

    /** Usada por Render (healthCheckPath) y para comprobar la conexión a la base. */
    public function salud(): Respuesta
    {
        try {
            $version = BaseDatos::conexion()->query('SHOW server_version')->fetchColumn();
            return $this->json(['estado' => 'ok', 'base_datos' => 'conectada', 'postgresql' => $version]);
        } catch (Throwable $error) {
            $detalle = Configuracion::obtener('depuracion') ? $error->getMessage() : 'sin conexión';
            return $this->json(['estado' => 'degradado', 'base_datos' => $detalle], 503);
        }
    }
}
