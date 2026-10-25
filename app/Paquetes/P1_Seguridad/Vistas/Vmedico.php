<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;

/** CU05 · Directorio de médicos y su formulario. */
final class Vmedico extends Vista
{
    public function mostrarLista(array $medicos, array $especialidades, int $desactivarId, array $afectadas): Respuesta
    {
        return $this->render('medicos/lista', [
            'titulo'         => 'Médicos',
            'migas'          => 'Clínica',
            'medicos'        => $medicos,
            'especialidades' => $especialidades,
            'desactivar'     => $desactivarId ? (current(array_filter($medicos, static fn (array $m): bool => (int) $m['id'] === $desactivarId)) ?: null) : null,
            'afectadas'      => $afectadas,
        ]);
    }

    public function mostrarFormulario(?array $medico, array $especialidades, array $extra): Respuesta
    {
        return $this->render('medicos/formulario', [
            'titulo'         => $medico ? 'Editar médico' : 'Registrar médico',
            'migas'          => 'Médicos',
            'medico'         => $medico,
            'especialidades' => $especialidades,
        ] + $extra);
    }
}
