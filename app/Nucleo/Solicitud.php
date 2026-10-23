<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

/** Datos de la solicitud HTTP actual, sin depender de las superglobales en el resto del código. */
final class Solicitud
{
    public function __construct(
        private string $metodo,
        private string $ruta,
        private array $consulta = [],
        private array $cuerpo = [],
        private array $archivos = [],
        private array $servidor = [],
    ) {
    }

    public static function desdeGlobales(): self
    {
        $cuerpo = $_POST;
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($tipo, 'application/json')) {
            $cuerpo = json_decode((string) file_get_contents('php://input'), true) ?: [];
        }

        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        // Los formularios HTML solo envían GET/POST; _metodo permite PUT y DELETE.
        if ($metodo === 'POST' && isset($cuerpo['_metodo'])) {
            $metodo = strtoupper((string) $cuerpo['_metodo']);
        }

        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $ruta = '/' . trim(rawurldecode($ruta), '/');

        return new self($metodo, $ruta, $_GET, $cuerpo, $_FILES, $_SERVER);
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    /** Valor del cuerpo o de la cadena de consulta, en ese orden. */
    public function entrada(string $clave, mixed $defecto = null): mixed
    {
        return $this->cuerpo[$clave] ?? $this->consulta[$clave] ?? $defecto;
    }

    /** Todas las entradas, sin los campos de control del formulario. */
    public function todo(): array
    {
        $datos = array_merge($this->consulta, $this->cuerpo);
        unset($datos['_csrf'], $datos['_metodo']);
        return $datos;
    }

    /** Solo las claves indicadas (lista blanca para no aceptar campos inesperados). */
    public function solo(array $claves): array
    {
        $todo = $this->todo();
        return array_intersect_key($todo, array_flip($claves));
    }

    public function consulta(string $clave, mixed $defecto = null): mixed
    {
        return $this->consulta[$clave] ?? $defecto;
    }

    public function archivo(string $clave): ?array
    {
        $archivo = $this->archivos[$clave] ?? null;
        return ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $archivo : null;
    }

    public function cabecera(string $nombre): ?string
    {
        $clave = 'HTTP_' . strtoupper(str_replace('-', '_', $nombre));
        return $this->servidor[$clave] ?? null;
    }

    /** IP del cliente; detrás del proxy de Render se toma la primera de X-Forwarded-For. */
    public function ip(): ?string
    {
        $reenviada = $this->cabecera('X-Forwarded-For');
        $ip = $reenviada ? trim(explode(',', $reenviada)[0]) : ($this->servidor['REMOTE_ADDR'] ?? null);
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    public function esApi(): bool
    {
        return str_starts_with($this->ruta, '/api/')
            || str_contains((string) $this->cabecera('Accept'), 'application/json');
    }

    /** Página desde la que se envió el formulario (solo ruta y consulta, nunca otro dominio). */
    public function anterior(): string
    {
        $partes = parse_url((string) $this->cabecera('Referer'));
        $ruta = $partes['path'] ?? '/';
        return $ruta . (isset($partes['query']) ? '?' . $partes['query'] : '');
    }

    public function esSegura(): bool
    {
        return ($this->servidor['HTTPS'] ?? '') === 'on' || $this->cabecera('X-Forwarded-Proto') === 'https';
    }
}
