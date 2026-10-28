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

    /** Campos de texto que el médico y la recepcionista pueden editar. */
    public const CAMPOS_TEXTO = [
        'alergias'                   => 'Alergias',
        'intolerancias_medicamentos' => 'Intolerancias a medicamentos',
        'enfermedades_cronicas'      => 'Enfermedades crónicas',
        'medicamentos_actuales'      => 'Medicamentos actuales',
        'antecedentes_personales'    => 'Antecedentes personales',
        'antecedentes_familiares'    => 'Antecedentes familiares',
    ];

    public function dePaciente(int $pacienteId): ?array
    {
        return $this->buscarPorId($pacienteId);
    }

    /** Paso 3: la ficha con la fecha de la última actualización y quién la hizo. */
    public function conResponsable(int $pacienteId): ?array
    {
        return $this->primero(
            "SELECT f.*, u.nombre_usuario AS actualizada_por_usuario, dp.nombres || ' ' || dp.apellidos AS actualizada_por_nombre
               FROM ficha_medica f
               JOIN usuario u ON u.id = f.actualizada_por
               JOIN datos_personales dp ON dp.id = u.datos_personales_id
              WHERE f.paciente_id = ?",
            [$pacienteId]
        );
    }

    public function crear(int $pacienteId, int $usuarioId, ?string $tipoSangre = null, ?string $alergias = null): void
    {
        $this->ejecutar(
            'INSERT INTO ficha_medica (paciente_id, tipo_sangre, alergias, actualizada_por) VALUES (?, ?, ?, ?)',
            [$pacienteId, $tipoSangre ?: null, $alergias ?: null, $usuarioId]
        );
    }

    /** Paso 5: guarda los datos, la fecha y el responsable (crea la ficha si faltara). */
    public function guardar(int $pacienteId, array $datos, int $usuarioId): void
    {
        $columnas = ['tipo_sangre', ...array_keys(self::CAMPOS_TEXTO)];
        $valores = array_map(static fn (string $c) => ($datos[$c] ?? '') === '' ? null : $datos[$c], $columnas);
        $actualizar = implode(', ', array_map(static fn (string $c): string => "$c = EXCLUDED.$c", $columnas));
        $this->ejecutar(
            'INSERT INTO ficha_medica (paciente_id, ' . implode(', ', $columnas) . ', fecha_actualizacion, actualizada_por)
             VALUES (?, ' . implode(', ', array_fill(0, count($columnas), '?')) . ', now(), ?)
             ON CONFLICT (paciente_id) DO UPDATE SET ' . $actualizar . ', fecha_actualizacion = now(), actualizada_por = EXCLUDED.actualizada_por',
            [$pacienteId, ...$valores, $usuarioId]
        );
    }
}
