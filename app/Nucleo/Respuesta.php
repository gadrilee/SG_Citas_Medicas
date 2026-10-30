<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

final class Respuesta
{
    public function __construct(
        private string $contenido = '',
        private int $codigo = 200,
        private array $cabeceras = [],
    ) {
    }

    public static function html(string $contenido, int $codigo = 200): self
    {
        return new self($contenido, $codigo, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $datos, int $codigo = 200): self
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return new self($json, $codigo, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirigir(string $ruta, int $codigo = 303): self
    {
        return new self('', $codigo, ['Location' => url($ruta)]);
    }

    /** Envía un archivo (informe PDF) para verlo en el navegador. */
    public static function archivo(string $ruta, string $tipo, string $nombre): self
    {
        return new self((string) file_get_contents($ruta), 200, [
            'Content-Type'        => $tipo,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $nombre) . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function codigo(): int
    {
        return $this->codigo;
    }

    public function contenido(): string
    {
        return $this->contenido;
    }

    public function cabecera(string $nombre): ?string
    {
        return $this->cabeceras[$nombre] ?? null;
    }

    public function enviar(): void
    {
        http_response_code($this->codigo);
        $seguridad = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'DENY',
            'Referrer-Policy'        => 'same-origin',
        ];
        foreach ($seguridad + $this->cabeceras as $nombre => $valor) {
            header($nombre . ': ' . $valor);
        }
        echo $this->contenido;
    }
}
