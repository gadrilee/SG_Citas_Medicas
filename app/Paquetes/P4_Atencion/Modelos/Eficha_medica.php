<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Historia clínica del paciente (dueño: CU16). CU04 y el registro web crean la ficha inicial. */
final class Eficha_medica extends Modelo
{
    protected const TABLA = 'ficha_medica';
    protected const CLAVE = 'paciente_id';

    public const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function dePaciente(int $pacienteId): ?array
    {
        return $this->buscarPorId($pacienteId);
    }

    public function crear(int $pacienteId, int $usuarioId, ?string $tipoSangre = null, ?string $alergias = null): void
    {
        $this->ejecutar(
            'INSERT INTO ficha_medica (paciente_id, tipo_sangre, alergias, actualizada_por) VALUES (?, ?, ?, ?)',
            [$pacienteId, $tipoSangre ?: null, $alergias ?: null, $usuarioId]
        );
    }
}
