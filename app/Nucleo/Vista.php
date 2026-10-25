<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use RuntimeException;

/**
 * Base de las clases de vista (V*). Cada vista prepara los datos de una pantalla y renderiza una
 * plantilla PHP de su paquete dentro de un layout compartido.
 *
 *   Vcita (VidaSalud\Paquetes\P2_Agenda\Vistas) → app/Paquetes/P2_Agenda/plantillas/<plantilla>.php
 *   Vinicio (VidaSalud\Compartido\Vistas)       → app/Compartido/plantillas/<plantilla>.php
 */
abstract class Vista
{
    /** Layout por defecto: 'panel', 'publico' o 'autenticacion'. */
    protected string $layout = 'panel';

    protected function render(string $plantilla, array $datos = [], ?string $layout = null, int $codigo = 200): Respuesta
    {
        [$errores, $anterior] = Sesion::consumirFormulario();
        $datos += ['titulo' => 'Vida Salud', 'errores' => $errores, 'anterior' => $anterior];

        $contenido = self::incluir($this->carpetaPlantillas() . '/' . $plantilla . '.php', $datos);
        $layout ??= $this->layout;
        $html = self::incluir(RAIZ . '/app/Compartido/plantillas/layouts/' . $layout . '.php', $datos + ['contenido' => $contenido]);

        return Respuesta::html($html, $codigo);
    }

    /** Fragmento sin layout (útil para respuestas parciales o impresión). */
    protected function fragmento(string $plantilla, array $datos = []): Respuesta
    {
        return Respuesta::html(self::incluir($this->carpetaPlantillas() . '/' . $plantilla . '.php', $datos));
    }

    private function carpetaPlantillas(): string
    {
        $partes = explode('\\', static::class);
        $indice = array_search('Paquetes', $partes, true);
        if ($indice !== false) {
            return RAIZ . '/app/Paquetes/' . $partes[$indice + 1] . '/plantillas';
        }
        return RAIZ . '/app/Compartido/plantillas';
    }

    /** Ejecuta una plantilla con sus variables aisladas y devuelve el HTML generado. */
    public static function incluir(string $archivo, array $datos): string
    {
        if (!is_file($archivo)) {
            throw new RuntimeException('No existe la plantilla ' . str_replace(RAIZ, '', $archivo));
        }
        extract($datos, EXTR_SKIP);
        ob_start();
        try {
            require $archivo;
        } catch (\Throwable $error) {
            ob_end_clean();
            throw $error;
        }
        return (string) ob_get_clean();
    }
}
