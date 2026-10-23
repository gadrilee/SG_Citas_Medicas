<?php

declare(strict_types=1);

namespace VidaSalud\Nucleo;

use PDOException;
use Throwable;
use VidaSalud\Compartido\Vistas\Verror;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoAutenticado;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Excepciones\ExcepcionValidacion;

/** Ciclo de vida de una solicitud: sesión → enrutamiento → controlador → respuesta o página de error. */
final class Aplicacion
{
    public function ejecutar(): void
    {
        $solicitud = Solicitud::desdeGlobales();
        Sesion::iniciar($solicitud->esSegura());
        Bitacora::definirIp($solicitud->ip());

        $this->procesar($solicitud)->enviar();
    }

    public function procesar(Solicitud $solicitud): Respuesta
    {
        try {
            return $this->enrutador()->despachar($solicitud);
        } catch (ExcepcionValidacion $error) {
            if ($solicitud->esApi()) {
                return Respuesta::json(['mensaje' => $error->getMessage(), 'errores' => $error->errores()], 422);
            }
            Sesion::guardarFormulario($error->errores(), $error->entradas());
            Sesion::flash('error', $error->getMessage());
            return Respuesta::redirigir($solicitud->anterior());
        } catch (ExcepcionNegocio $error) {
            return $this->rechazar($solicitud, $error->getMessage());
        } catch (PDOException $error) {
            // Restricciones de PostgreSQL que protegen las reglas aunque falle la validación previa.
            $mensaje = self::MENSAJES_BD[$error->getCode()] ?? null;
            return $mensaje === null ? $this->errorInesperado($solicitud, $error) : $this->rechazar($solicitud, $mensaje);
        } catch (ExcepcionNoAutenticado) {
            if ($solicitud->esApi()) {
                return Respuesta::json(['mensaje' => 'Inicia sesión para continuar.'], 401);
            }
            if ($solicitud->metodo() === 'GET') {
                Sesion::poner('_destino', $solicitud->rutaConConsulta());
            }
            return Respuesta::redirigir('/login');
        } catch (ExcepcionAcceso $error) {
            return $this->error($solicitud, 403, $error->getMessage());
        } catch (ExcepcionNoEncontrado $error) {
            return $this->error($solicitud, 404, $error->getMessage());
        } catch (Throwable $error) {
            return $this->errorInesperado($solicitud, $error);
        }
    }

    /** El detalle técnico solo va al log; el usuario ve un mensaje genérico. */
    private function errorInesperado(Solicitud $solicitud, Throwable $error): Respuesta
    {
        error_log('[Vida Salud] ' . $error::class . ': ' . $error->getMessage() . ' en ' . $error->getFile() . ':' . $error->getLine());
        $detalle = Configuracion::obtener('depuracion') ? $error->getMessage() : null;
        return $this->error($solicitud, 500, 'Ocurrió un error inesperado. Intenta nuevamente.', $detalle);
    }

    private const MENSAJES_BD = [
        '23505' => 'Ya existe un registro con esos datos.',
        '23P01' => 'El horario seleccionado ya no está disponible.',
        '23514' => 'Los datos no cumplen las reglas del sistema.',
        '23503' => 'El registro está relacionado con otros datos y no se puede modificar así.',
    ];

    private function rechazar(Solicitud $solicitud, string $mensaje): Respuesta
    {
        if ($solicitud->esApi()) {
            return Respuesta::json(['mensaje' => $mensaje], 409);
        }
        Sesion::guardarFormulario([], $solicitud->todo());
        Sesion::flash('error', $mensaje);
        return Respuesta::redirigir($solicitud->anterior());
    }

    private function enrutador(): Enrutador
    {
        $enrutador = new Enrutador();
        $enrutador->cargarArchivo(RAIZ . '/app/Compartido/rutas.php');
        foreach (glob(RAIZ . '/app/Paquetes/*/rutas.php') as $archivo) {
            $enrutador->cargarArchivo($archivo);
        }
        return $enrutador;
    }

    private function error(Solicitud $solicitud, int $codigo, string $mensaje, ?string $detalle = null): Respuesta
    {
        if ($solicitud->esApi()) {
            return Respuesta::json(['mensaje' => $mensaje], $codigo);
        }
        try {
            return (new Verror())->mostrar($codigo, $mensaje, $detalle);
        } catch (Throwable) {
            return Respuesta::html('<h1>' . $codigo . '</h1><p>' . htmlspecialchars($mensaje) . '</p>', $codigo);
        }
    }
}
