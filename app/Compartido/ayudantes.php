<?php

declare(strict_types=1);

// Funciones cortas para las plantillas. Mantienen el HTML legible y escapan siempre la salida.

use VidaSalud\Nucleo\Autorizacion;
use VidaSalud\Nucleo\Csrf;
use VidaSalud\Nucleo\Sesion;
use VidaSalud\Nucleo\Vista;

/** Escapa texto para HTML (usar en TODA salida de datos: <?= e($cita['codigo']) ?>). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta): string
{
    return '/' . ltrim($ruta, '/');
}

/** Archivo de /publico con versión por fecha de modificación para evitar caché vieja. */
function recurso(string $archivo): string
{
    $ruta = RAIZ . '/publico/' . ltrim($archivo, '/');
    $version = is_file($ruta) ? filemtime($ruta) : 0;
    return url($archivo) . '?v=' . $version;
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function metodo_campo(string $metodo): string
{
    return '<input type="hidden" name="_metodo" value="' . e(strtoupper($metodo)) . '">';
}

/** Incluye un parcial de app/Compartido/plantillas/parciales. */
function parcial(string $nombre, array $datos = []): string
{
    return Vista::incluir(RAIZ . '/app/Compartido/plantillas/parciales/' . $nombre . '.php', $datos);
}

function icono(string $nombre, string $clases = 'w-4 h-4'): string
{
    return '<i data-lucide="' . e($nombre) . '" class="' . e($clases) . '" aria-hidden="true"></i>';
}

function usuario_actual(): ?array
{
    return Sesion::usuario();
}

function puede(string $privilegio): bool
{
    return Autorizacion::estaAutenticado() && Autorizacion::tienePrivilegio($privilegio);
}

function ruta_actual(): string
{
    return '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
}

/** Valor de un campo de formulario: lo reenviado tras un error tiene prioridad sobre el registro. */
function valor(array $anterior, array $registro, string $campo, mixed $defecto = ''): string
{
    return (string) ($anterior[$campo] ?? $registro[$campo] ?? $defecto);
}

/** Mensaje de error de validación del servidor para un campo. */
function error_campo(array $errores, string $campo): string
{
    return '<p class="error-campo" data-error="' . e($campo) . '">' . e($errores[$campo] ?? '') . '</p>';
}

/** Atributos de un campo con error: clase y aria-invalid. */
function marca_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' campo-error" aria-invalid="true' : '';
}

/** 150 → "Bs 150,00" */
function bs(float|int|string|null $monto): string
{
    return 'Bs ' . number_format((float) $monto, 2, ',', '.');
}

/** '2026-11-16 09:00:00-04' → '16/11/2026' */
function fecha_corta(?string $fecha): string
{
    return $fecha ? (new DateTimeImmutable($fecha))->format('d/m/Y') : '';
}

/** '2026-11-16 09:00:00-04' → '16/11/2026 09:00' */
function fecha_hora(?string $fecha): string
{
    return $fecha ? (new DateTimeImmutable($fecha))->format('d/m/Y H:i') : '';
}

/** '2026-11-16 09:00:00-04' → '09:00' */
function hora_corta(?string $fecha): string
{
    return $fecha ? (new DateTimeImmutable($fecha))->format('H:i') : '';
}

/** Rango de una cita: '09:00–09:15' */
function rango_horas(?string $inicio, ?string $fin): string
{
    return hora_corta($inicio) . '–' . hora_corta($fin);
}

function edad(?string $fechaNacimiento): string
{
    return $fechaNacimiento ? (new DateTimeImmutable($fechaNacimiento))->diff(new DateTimeImmutable('today'))->y . ' años' : '';
}

/** Iniciales para avatares: 'María López' → 'ML' */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    return mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
}

/** Foto de demostración de un médico según su código (M-001…); null si no hay foto. */
function foto_medico(?string $codigo): ?string
{
    $fotos = ['M-001' => 'perez', 'M-002' => 'suarez', 'M-003' => 'gutierrez', 'M-004' => 'mendez', 'M-005' => 'antelo'];
    return isset($fotos[$codigo]) ? 'img/medico-' . $fotos[$codigo] . '.jpg' : null;
}

/** Texto visible de un estado: tipo cita | pago | estudio | activo. */
function texto_estado(string $tipo, string|bool $estado): string
{
    if ($tipo === 'activo') {
        return $estado ? 'Activo' : 'Inactivo';
    }
    return ['en_proceso' => 'En proceso'][$estado] ?? ucfirst((string) $estado);
}

/** Clase de la insignia de V0: estado-programada, pago-aprobado, estudio-disponible, estado-activo… */
function clase_estado(string $tipo, string|bool $estado): string
{
    return match ($tipo) {
        'activo' => $estado ? 'estado-activo' : 'estado-inactivo',
        'cita'   => 'estado-' . $estado,
        default  => $tipo . '-' . $estado,
    };
}

/** Insignia de perfil con el color usado en la lista de usuarios de V0. */
function clase_perfil(string $perfil): string
{
    return [
        'administrador' => 'insignia-acento',
        'recepcionista' => 'insignia-info',
        'medico'        => 'insignia-exito',
    ][$perfil] ?? 'insignia-neutra';
}

function nombre_perfil(string $perfil): string
{
    return ['medico' => 'Médico'][$perfil] ?? ucfirst($perfil);
}
