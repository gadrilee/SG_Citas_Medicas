<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Vistas;

use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Nucleo\Vista;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;

/** CU17 y CU18 · Registrar consulta con tratamientos y estudios, o ver la consulta ya registrada. */
final class Vconsulta extends Vista
{
    public function mostrarFormulario(array $cita, array $paciente, ?array $ficha, array $consultas, array $resultados): Respuesta
    {
        return $this->render('consultas/registrar', [
            'titulo'     => 'Consulta ' . $cita['codigo'],
            'migas'      => 'Mi agenda',
            'cita'       => $cita,
            'paciente'   => $paciente,
            'ficha'      => $ficha ?? [],
            'consultas'  => $consultas,
            'resultados' => $resultados,
            'campos'     => Eficha_medica::CAMPOS_TEXTO,
        ]);
    }

    public function mostrarDetalle(array $cita, array $consulta, array $tratamientos, array $estudios, array $paciente, ?array $ficha, array $consultas, array $resultados): Respuesta
    {
        return $this->render('consultas/detalle', [
            'titulo'       => 'Consulta ' . $cita['codigo'],
            'migas'        => 'Mi agenda',
            'cita'         => $cita,
            'consulta'     => $consulta,
            'tratamientos' => $tratamientos,
            'estudios'     => $estudios,
            'paciente'     => $paciente,
            'ficha'        => $ficha ?? [],
            'consultas'    => array_values(array_filter($consultas, static fn (array $c): bool => (int) $c['id'] !== (int) $consulta['id'])),
            'resultados'   => $resultados,
            'campos'       => Eficha_medica::CAMPOS_TEXTO,
        ]);
    }
}
