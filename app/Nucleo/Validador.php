<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use DateTimeImmutable;

/**
 * Validación en el servidor. Las reglas se escriben como texto separado por "|":
 *   ['ci' => 'requerido|max:30', 'correo' => 'opcional|correo', 'fecha_nacimiento' => 'requerido|fecha|fecha_no_futura']
 */
final class Validador
{
    private const MENSAJES = [
        'requerido'       => 'Este campo es obligatorio.',
        'correo'          => 'Ingresa un correo válido.',
        'entero'          => 'Debe ser un número entero.',
        'decimal'         => 'Debe ser un número válido.',
        'fecha'           => 'Ingresa una fecha válida (AAAA-MM-DD).',
        'hora'            => 'Ingresa una hora válida (HH:MM).',
        'fecha_hora'      => 'Ingresa una fecha y hora válidas.',
        'fecha_no_futura' => 'La fecha no puede ser posterior a hoy.',
        'fecha_no_pasada' => 'La fecha no puede ser anterior a hoy.',
        'min'             => 'Debe tener al menos :valor caracteres.',
        'max'             => 'Debe tener como máximo :valor caracteres.',
        'en'              => 'Selecciona una opción válida.',
        'coincide'        => 'Los valores no coinciden.',
        'sin_arroba'      => 'No puede contener el carácter «@».',
        'nombre_usuario'  => 'Usa de 3 a 50 letras minúsculas, números, punto o guion bajo.',
        'contrasena'      => 'Debe tener al menos 8 caracteres con letras y números.',
        'telefono'        => 'Ingresa un teléfono válido (solo números, 7 a 15 dígitos).',
        'arreglo'         => 'El formato enviado no es válido.',
    ];

    /** Devuelve [campo => mensaje] solo con los campos que fallaron. */
    public static function validar(array $datos, array $reglas): array
    {
        $errores = [];
        foreach ($reglas as $campo => $definicion) {
            $valor = $datos[$campo] ?? null;
            $lista = explode('|', $definicion);
            $vacio = $valor === null || (is_string($valor) && trim($valor) === '') || $valor === [];

            if ($vacio) {
                if (in_array('requerido', $lista, true)) {
                    $errores[$campo] = self::MENSAJES['requerido'];
                }
                continue;
            }

            foreach ($lista as $regla) {
                [$nombre, $parametro] = array_pad(explode(':', $regla, 2), 2, null);
                if (in_array($nombre, ['requerido', 'opcional'], true)) {
                    continue;
                }
                if (!self::cumple($nombre, $valor, $parametro, $datos)) {
                    $errores[$campo] = str_replace(':valor', (string) $parametro, self::MENSAJES[$nombre] ?? 'Valor no válido.');
                    break;
                }
            }
        }
        return $errores;
    }

    private static function cumple(string $regla, mixed $valor, ?string $parametro, array $datos): bool
    {
        $texto = is_scalar($valor) ? trim((string) $valor) : '';
        return match ($regla) {
            'correo'          => filter_var($texto, FILTER_VALIDATE_EMAIL) !== false,
            'entero'          => filter_var($texto, FILTER_VALIDATE_INT) !== false,
            'decimal'         => is_numeric($texto),
            'fecha'           => self::fechaValida($texto, 'Y-m-d'),
            'hora'            => self::fechaValida($texto, 'H:i') || self::fechaValida($texto, 'H:i:s'),
            'fecha_hora'      => strtotime($texto) !== false,
            'fecha_no_futura' => self::fechaValida($texto, 'Y-m-d') && $texto <= date('Y-m-d'),
            'fecha_no_pasada' => self::fechaValida($texto, 'Y-m-d') && $texto >= date('Y-m-d'),
            'min'             => mb_strlen($texto) >= (int) $parametro,
            'max'             => mb_strlen($texto) <= (int) $parametro,
            'en'              => in_array($texto, explode(',', (string) $parametro), true),
            'coincide'        => $texto === trim((string) ($datos[$parametro] ?? '')),
            'sin_arroba'      => !str_contains($texto, '@'),
            'nombre_usuario'  => (bool) preg_match('/^[a-z0-9._]{3,50}$/', $texto),
            'contrasena'      => mb_strlen($texto) >= 8 && preg_match('/[A-Za-z]/', $texto) && preg_match('/\d/', $texto),
            'telefono'        => (bool) preg_match('/^\+?[0-9 ]{7,15}$/', $texto),
            'arreglo'         => is_array($valor),
            default           => true,
        };
    }

    private static function fechaValida(string $texto, string $formato): bool
    {
        $fecha = DateTimeImmutable::createFromFormat('!' . $formato, $texto);
        return $fecha !== false && $fecha->format($formato) === $texto;
    }
}
