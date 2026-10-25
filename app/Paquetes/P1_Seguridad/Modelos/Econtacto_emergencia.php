<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

final class Econtacto_emergencia extends Modelo
{
    protected const TABLA = 'contacto_emergencia';

    public function dePaciente(int $pacienteId): array
    {
        return $this->consultar('SELECT id, nombre, parentesco, telefono FROM contacto_emergencia WHERE paciente_id = ? ORDER BY id', [$pacienteId]);
    }

    /** Reemplaza la lista completa (no tiene registros dependientes). Ignora filas vacías. */
    public function reemplazar(int $pacienteId, array $contactos): void
    {
        $this->ejecutar('DELETE FROM contacto_emergencia WHERE paciente_id = ?', [$pacienteId]);
        foreach ($contactos as $contacto) {
            $this->insertar([
                'paciente_id' => $pacienteId,
                'nombre'      => trim($contacto['nombre']),
                'parentesco'  => trim($contacto['parentesco']),
                'telefono'    => trim($contacto['telefono']),
            ]);
        }
    }
}
