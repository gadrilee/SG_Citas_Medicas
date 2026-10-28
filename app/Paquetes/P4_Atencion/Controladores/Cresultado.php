<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Controladores;

use finfo;
use Throwable;
use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eestudio_laboratorio as Estudio;
use VidaSalud\Paquetes\P4_Atencion\Vistas\Vresultado;

/** CU19 · Gestionar resultados de laboratorio. El médico publica; el paciente ve solo los suyos publicados. */
final class Cresultado extends Controlador
{
    private const TAMANO_MAXIMO = 5 * 1024 * 1024;
    private const FILTROS = ['pendientes', Estudio::PENDIENTE, Estudio::EN_PROCESO, Estudio::DISPONIBLE, ''];

    /** Paso 1: estudios pendientes o en proceso de los pacientes del médico. */
    public function listar(): Respuesta
    {
        $medicoId = (int) $this->usuario()['datos_personales_id'];
        $estado = (string) $this->solicitud->consulta('estado', 'pendientes');
        $estado = in_array($estado, self::FILTROS, true) ? $estado : 'pendientes';
        $buscar = trim((string) $this->solicitud->consulta('buscar', ''));
        $estudios = new Estudio();
        return (new Vresultado())->mostrarEstudios(
            $estudios->delMedico($medicoId, $estado, $buscar, (int) $this->solicitud->consulta('pagina', 1)),
            $estudios->contarPorEstado($medicoId),
            ['estado' => $estado, 'buscar' => $buscar]
        );
    }

    /** Paso 2 y A1: el resultado aún no está listo; el paciente no lo ve. */
    public function marcarEnProceso(string $id): Respuesta
    {
        $estudio = $this->estudioDelMedico($id);
        BaseDatos::transaccion(function () use ($estudio): void {
            $modelo = new Estudio();
            $actual = $modelo->paraActualizar((int) $estudio['id']);
            if (!Estudio::transicionPermitida($actual['estado'], Estudio::EN_PROCESO)) {
                throw new ExcepcionNegocio('El estudio ya está ' . texto_estado('estudio', $actual['estado']) . '.');
            }
            $modelo->marcarEnProceso((int) $actual['id']);
            Bitacora::registrar('marcar_en_proceso', 'estudio_laboratorio', (int) $actual['id']);
        });
        return $this->volver('exito', '«' . $estudio['nombre_estudio'] . '» de ' . $estudio['paciente'] . ' quedó en proceso.');
    }

    /** Pasos 3–5: resultado en texto y/o PDF; queda disponible con fecha de publicación y responsable. */
    public function publicar(string $id): Respuesta
    {
        $estudio = $this->estudioDelMedico($id);
        $texto = trim((string) $this->solicitud->entrada('resultado_texto', ''));
        $archivo = $this->solicitud->archivo('archivo');
        if ($texto === '' && !$archivo) {
            throw new ExcepcionNegocio('Registra el resultado en texto o adjunta el informe en PDF.');   // E1
        }
        if (mb_strlen($texto) > 10000) {
            throw new ExcepcionNegocio('El texto del resultado no puede superar los 10 000 caracteres.');
        }
        $referencia = $archivo ? $this->guardarPdf($archivo) : null;   // E2

        try {
            BaseDatos::transaccion(function () use ($estudio, $texto, $referencia): void {
                $modelo = new Estudio();
                $actual = $modelo->paraActualizar((int) $estudio['id']);
                if (!Estudio::transicionPermitida($actual['estado'], Estudio::DISPONIBLE)) {
                    throw new ExcepcionNegocio('El estudio ya fue publicado y no puede modificarse.');   // E4
                }
                $modelo->publicar((int) $actual['id'], $texto === '' ? null : $texto, $referencia, (int) $this->usuario()['id']);
                Bitacora::registrar('publicar', 'estudio_laboratorio', (int) $actual['id']);
            });
        } catch (Throwable $error) {
            if ($referencia) {
                @unlink(self::carpeta() . '/' . $referencia);
            }
            throw $error;
        }
        return $this->volver('exito', 'Resultado de «' . $estudio['nombre_estudio'] . '» publicado: ' . $estudio['paciente'] . ' ya puede verlo.');
    }

    /** Paso 6 y E3: el paciente ve solo sus estudios publicados. */
    public function misResultados(): Respuesta
    {
        if ($this->usuario()['perfil'] !== 'paciente') {
            return $this->redirigir($this->usuario()['perfil'] === 'medico' ? '/estudios' : '/panel');
        }
        return (new Vresultado())->mostrarMisResultados(
            (new Estudio())->publicadosDePaciente((int) $this->usuario()['datos_personales_id'])
        );
    }

    /** Descarga del informe: el paciente el suyo publicado; el médico los de sus pacientes. */
    public function descargar(string $id): Respuesta
    {
        $estudio = (new Estudio())->buscarDetalle((int) $id);
        $usuario = $this->usuario();
        $propio = (int) $usuario['datos_personales_id'];
        $permitido = $estudio && $estudio['estado'] === Estudio::DISPONIBLE && match ($usuario['perfil']) {
            'paciente' => (int) $estudio['paciente_id'] === $propio,
            'medico'   => (int) $estudio['medico_id'] === $propio || (new Ecita_medica())->medicoAtiendeHoy($propio, (int) $estudio['paciente_id']),
            default    => false,
        };
        // E3: para quien no puede verlo, el estudio simplemente no existe.
        if (!$permitido || !preg_match('#^resultados/[a-f0-9]{32}\.pdf$#', (string) $estudio['archivo_referencia'])) {
            throw new ExcepcionNoEncontrado('El informe no existe o no está disponible.');
        }
        $ruta = self::carpeta() . '/' . $estudio['archivo_referencia'];
        if (!is_file($ruta)) {
            throw new ExcepcionNoEncontrado('El archivo del informe ya no está en el servidor; consulta el resultado en texto.');
        }
        Bitacora::registrar('descargar', 'estudio_laboratorio', (int) $estudio['id']);
        return Respuesta::archivo($ruta, 'application/pdf', 'resultado-' . $estudio['id'] . '.pdf');
    }

    private function estudioDelMedico(string $id): array
    {
        $estudio = (new Estudio())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('El estudio no existe.');
        if ((int) $estudio['medico_id'] !== (int) $this->usuario()['datos_personales_id']) {
            throw new ExcepcionAcceso('Solo puedes gestionar los estudios que solicitaste.');
        }
        return $estudio;
    }

    /** E2: solo PDF de hasta 5 MB. Se guarda fuera de /publico con un nombre aleatorio. */
    private function guardarPdf(array $archivo): string
    {
        if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $archivo['size'] > self::TAMANO_MAXIMO) {
            throw new ExcepcionNegocio('El informe supera el tamaño máximo de 5 MB.');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            throw new ExcepcionNegocio('No se pudo recibir el archivo; intenta nuevamente.');
        }
        if ((new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']) !== 'application/pdf') {
            throw new ExcepcionNegocio('El informe debe ser un archivo PDF.');
        }
        $carpeta = self::carpeta() . '/resultados';
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new ExcepcionNegocio('No se pudo guardar el informe en el servidor.');
        }
        $referencia = 'resultados/' . bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($archivo['tmp_name'], self::carpeta() . '/' . $referencia)) {
            throw new ExcepcionNegocio('No se pudo guardar el informe en el servidor.');
        }
        return $referencia;
    }

    private static function carpeta(): string
    {
        return RAIZ . '/almacen';
    }
}
