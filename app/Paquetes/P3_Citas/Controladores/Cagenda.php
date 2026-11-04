<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P3_Citas\Controladores;

use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Validador;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Emedico;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ebloqueo_agenda;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Eestado_cita;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ehorario_atencion;
use VidaSalud\Paquetes\P3_Citas\Vistas\Vagenda;

/** CU13 · Consultar agenda médica (solo lectura). El médico solo ve la suya. */
final class Cagenda extends Controlador
{
    public function mostrar(): Respuesta
    {
        $usuario = $this->usuario();
        $filtros = $this->solicitud->solo(['fecha', 'medico_id', 'estado_cita_id']) + ['fecha' => date('Y-m-d')];
        $errores = Validador::validar($filtros, ['fecha' => 'requerido|fecha', 'medico_id' => 'opcional|entero', 'estado_cita_id' => 'opcional|entero']);
        if ($errores) {
            $filtros['fecha'] = date('Y-m-d');
        }

        $medicos = (new Emedico())->activosConEspecialidades();
        if ($usuario['perfil'] === 'medico') {
            // Paso 2 y E1: el médico ve automáticamente su agenda y no la de otro.
            $propio = (int) $usuario['datos_personales_id'];
            if (($filtros['medico_id'] ?? '') !== '' && (int) $filtros['medico_id'] !== $propio) {
                throw new ExcepcionAcceso('Solo puedes consultar tu propia agenda.');
            }
            $filtros['medico_id'] = $propio;
            $medicos = array_values(array_filter($medicos, static fn (array $m): bool => (int) $m['id'] === $propio));
        }

        $medico = null;
        foreach ($medicos as $opcion) {
            if ((string) $opcion['id'] === (string) ($filtros['medico_id'] ?? '')) {
                $medico = $opcion;
            }
        }
        $medico ??= $medicos[0] ?? null;
        if (!$medico) {
            return (new Vagenda())->mostrar(null, [], [], [], [], null, $filtros, (new Eestado_cita())->todos(), $errores);
        }

        $medicoId = (int) $medico['id'];
        $filtros['medico_id'] = $medicoId;
        $delDia = (new Ecita_medica())->delDia($filtros['fecha'], $medicoId);
        $estadoId = (int) ($filtros['estado_cita_id'] ?? 0);
        $citas = $estadoId ? array_values(array_filter($delDia, static fn (array $c): bool => (int) $c['estado_cita_id'] === $estadoId)) : $delDia;

        return (new Vagenda())->mostrar(
            $medico,
            $usuario['perfil'] === 'medico' ? [] : $medicos,
            $citas,
            array_count_values(array_column($delDia, 'estado')),
            (new Ehorario_atencion())->paraFecha($medicoId, $filtros['fecha']),
            (new Ebloqueo_agenda())->deFecha($medicoId, $filtros['fecha']),
            $filtros,
            (new Eestado_cita())->todos(),
            $errores
        );
    }
}
