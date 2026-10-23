<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P2_Agenda\Modelos;

use VidaSalud\Nucleo\Modelo;

/**
 * Ventanas semanales de atención de cada médico (CU07). dia_semana sigue ISO: 1 lunes … 7 domingo.
 * Una ventana "vigente" es la activa cuyo rango [vigente_desde, vigente_hasta] incluye la fecha.
 */
final class Ehorario_atencion extends Modelo
{
    protected const TABLA = 'horario_atencion';

    /** Ventanas vigentes hoy o en el futuro de un médico, por día y hora. */
    public function vigentesDe(int $medicoId): array
    {
        return $this->consultar(
            "SELECT id, dia_semana, to_char(hora_inicio, 'HH24:MI') AS hora_inicio, to_char(hora_fin, 'HH24:MI') AS hora_fin,
                    duracion_cita_min, vigente_desde, vigente_hasta
               FROM horario_atencion
              WHERE medico_id = ? AND activo AND (vigente_hasta IS NULL OR vigente_hasta >= current_date)
              ORDER BY dia_semana, hora_inicio",
            [$medicoId]
        );
    }

    /** Ventanas que aplican a una fecha concreta (CU08). */
    public function paraFecha(int $medicoId, string $fecha): array
    {
        return $this->consultar(
            "SELECT id, to_char(hora_inicio, 'HH24:MI') AS hora_inicio, to_char(hora_fin, 'HH24:MI') AS hora_fin, duracion_cita_min
               FROM horario_atencion
              WHERE medico_id = :medico AND activo
                AND dia_semana = EXTRACT(ISODOW FROM CAST(:f1 AS date))
                AND vigente_desde <= CAST(:f2 AS date)
                AND (vigente_hasta IS NULL OR vigente_hasta >= CAST(:f3 AS date))
              ORDER BY hora_inicio",
            ['medico' => $medicoId, 'f1' => $fecha, 'f2' => $fecha, 'f3' => $fecha]
        );
    }

    /** Días de la semana (1–7) en que el médico atiende a partir de hoy; sirve para el calendario. */
    public function diasDeAtencion(int $medicoId): array
    {
        return array_map('intval', array_column($this->consultar(
            'SELECT DISTINCT dia_semana FROM horario_atencion
              WHERE medico_id = ? AND activo AND (vigente_hasta IS NULL OR vigente_hasta >= current_date) ORDER BY dia_semana',
            [$medicoId]
        ), 'dia_semana'));
    }

    /** E3: otra ventana activa del mismo médico y día que se cruza en horas y en vigencia. */
    public function seSuperpone(int $medicoId, int $dia, string $inicio, string $fin, string $desde, ?string $hasta, ?int $excluirId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM horario_atencion
              WHERE medico_id = :medico AND dia_semana = :dia AND activo AND id <> :excluir
                AND hora_inicio < CAST(:fin AS time) AND hora_fin > CAST(:inicio AS time)
                AND daterange(vigente_desde, vigente_hasta, \'[]\') && daterange(CAST(:desde AS date), CAST(:hasta AS date), \'[]\')',
            ['medico' => $medicoId, 'dia' => $dia, 'excluir' => $excluirId ?? 0, 'fin' => $fin, 'inicio' => $inicio, 'desde' => $desde, 'hasta' => $hasta]
        );
    }

    /**
     * A1: modificar sin alterar lo ya reservado. La ventana actual termina el día anterior a $desde;
     * si aún no había empezado, simplemente se desactiva.
     */
    public function cerrarVigencia(int $id, string $desde): void
    {
        $this->ejecutar(
            "UPDATE horario_atencion
                SET vigente_hasta = CASE WHEN vigente_desde <= CAST(:d1 AS date) - 1 THEN CAST(:d2 AS date) - 1 ELSE vigente_hasta END,
                    activo        = vigente_desde <= CAST(:d3 AS date) - 1
              WHERE id = :id",
            ['d1' => $desde, 'd2' => $desde, 'd3' => $desde, 'id' => $id]
        );
    }
}
