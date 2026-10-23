<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Controladores;

use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eespecialidad;
use VidaSalud\Paquetes\P2_Agenda\Vistas\Vespecialidad;

/** CU06 · Gestionar especialidades (catálogo y tarifa de consulta). */
final class Cespecialidad extends Controlador
{
    private const REGLAS = [
        'nombre'           => 'requerido|max:100',
        'descripcion'      => 'opcional|max:500',
        'importe_consulta' => 'requerido|no_negativo',
    ];

    public function listar(): Respuesta
    {
        $especialidades = new Eespecialidad();
        $editarId = (int) $this->solicitud->consulta('editar');
        return (new Vespecialidad())->mostrarLista(
            $especialidades->todas(),
            $editarId ? $especialidades->buscarPorId($editarId) : null
        );
    }

    public function crear(): Respuesta
    {
        $datos = $this->datos();
        $especialidades = new Eespecialidad();
        if ($especialidades->existeNombre($datos['nombre'])) {
            throw new ExcepcionValidacion(['nombre' => 'Ya existe una especialidad con ese nombre.'], $this->solicitud->todo());
        }
        $id = $especialidades->insertar($datos);
        Bitacora::registrar('crear', 'especialidad', $id);
        return $this->redirigir('/especialidades', 'exito', 'Especialidad «' . $datos['nombre'] . '» creada.');
    }

    public function actualizar(string $id): Respuesta
    {
        $especialidad = $this->buscar($id);
        $datos = $this->datos();
        $especialidades = new Eespecialidad();
        if ($especialidades->existeNombre($datos['nombre'], (int) $especialidad['id'])) {
            throw new ExcepcionValidacion(['nombre' => 'Ya existe una especialidad con ese nombre.'], $this->solicitud->todo());
        }
        $especialidades->actualizar((int) $especialidad['id'], $datos);
        Bitacora::registrar('editar', 'especialidad', (int) $especialidad['id']);
        return $this->redirigir('/especialidades', 'exito', 'Especialidad «' . $datos['nombre'] . '» actualizada. La nueva tarifa se aplica a las próximas reservas.');
    }

    /** A1: deja de ofrecerse en nuevas reservas; las citas y asignaciones existentes se conservan (E3). */
    public function desactivar(string $id): Respuesta
    {
        $especialidad = $this->buscar($id);
        (new Eespecialidad())->cambiarEstado((int) $especialidad['id'], false);
        Bitacora::registrar('desactivar', 'especialidad', (int) $especialidad['id']);
        return $this->redirigir('/especialidades', 'exito', 'Especialidad «' . $especialidad['nombre'] . '» desactivada; ya no se ofrece en nuevas reservas.');
    }

    public function activar(string $id): Respuesta
    {
        $especialidad = $this->buscar($id);
        (new Eespecialidad())->cambiarEstado((int) $especialidad['id'], true);
        Bitacora::registrar('activar', 'especialidad', (int) $especialidad['id']);
        return $this->redirigir('/especialidades', 'exito', 'Especialidad «' . $especialidad['nombre'] . '» reactivada.');
    }

    private function buscar(string $id): array
    {
        return (new Eespecialidad())->buscarPorId((int) $id) ?? throw new ExcepcionNoEncontrado('La especialidad no existe.');
    }

    private function datos(): array
    {
        $datos = $this->validar(self::REGLAS);
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        return [
            'nombre'           => preg_replace('/\s+/', ' ', trim($datos['nombre'])),
            'descripcion'      => $descripcion !== '' ? $descripcion : null,
            'importe_consulta' => number_format((float) $datos['importe_consulta'], 2, '.', ''),
        ];
    }
}
