<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Identidad y contacto compartidos por pacientes, médicos y personal. */
final class Edatos_personales extends Modelo
{
    protected const TABLA = 'datos_personales';

    public const CAMPOS = ['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'correo', 'direccion'];

    private const CON_TIPOS = 'SELECT dp.*, (p.id IS NOT NULL) AS es_paciente, (m.id IS NOT NULL) AS es_medico, (pe.id IS NOT NULL) AS es_personal
                                 FROM datos_personales dp
                                 LEFT JOIN paciente p ON p.id = dp.id
                                 LEFT JOIN medico m ON m.id = dp.id
                                 LEFT JOIN personal pe ON pe.id = dp.id';

    /** Persona por CI, indicando si ya es paciente, médico o personal. */
    public function buscarPorCi(string $ci): ?array
    {
        return $this->primero(self::CON_TIPOS . ' WHERE upper(btrim(dp.ci)) = upper(btrim(?))', [$ci]);
    }

    public function buscarConTipos(int $id): ?array
    {
        return $this->primero(self::CON_TIPOS . ' WHERE dp.id = ?', [$id]);
    }

    public function existeCorreo(string $correo, ?int $excluirId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM datos_personales WHERE lower(btrim(correo)) = lower(btrim(?)) AND id <> ?',
            [$correo, $excluirId ?? 0]
        );
    }

    /** Normaliza la entrada del formulario: textos recortados y opcionales vacíos como NULL. */
    public static function normalizar(array $datos): array
    {
        $limpios = [];
        foreach (self::CAMPOS as $campo) {
            if (!array_key_exists($campo, $datos)) {
                continue;
            }
            $valor = trim((string) $datos[$campo]);
            $limpios[$campo] = $valor === '' ? null : $valor;
        }
        if (isset($limpios['ci'])) {
            $limpios['ci'] = mb_strtoupper(preg_replace('/\s+/', ' ', $limpios['ci']));
        }
        if (isset($limpios['correo'])) {
            $limpios['correo'] = mb_strtolower($limpios['correo']);
        }
        return $limpios;
    }
}
