<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Controladores;

use VidaSalud\Nucleo\Autorizacion;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P1_Seguridad\Modelos\Epaciente;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Econsulta;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Eficha_medica;
use VidaSalud\Paquetes\P4_Atencion\Vistas\Vficha_medica;

/** CU16 · Gestionar historia clínica. Médico y recepción la actualizan; el paciente solo la consulta. */
final class Cficha_medica extends Controlador
{
    /** Pasos 1–3: el médico la abre desde su agenda y la recepción desde la ficha del paciente. */
    public function mostrar(string $id): Respuesta
    {
        if ($this->usuario()['perfil'] === 'paciente') {
            return $this->redirigir('/mi-historia');
        }
        $paciente = $this->pacienteAccesible($id);
        $pacienteId = (int) $paciente['id'];
        $esMedico = $this->usuario()['perfil'] === 'medico';
        return (new Vficha_medica())->mostrar(
            $paciente,
            (new Eficha_medica())->conResponsable($pacienteId),
            // Paso 3: solo el médico ve además las consultas anteriores.
            $esMedico ? (new Econsulta())->dePaciente($pacienteId) : [],
            Autorizacion::tienePrivilegio('historia.actualizar'),
            false
        );
    }

    /** A1: el paciente consulta su propia ficha en modo de solo lectura. */
    public function miHistoria(): Respuesta
    {
        if ($this->usuario()['perfil'] !== 'paciente') {
            return $this->redirigir('/panel');
        }
        $pacienteId = (int) $this->usuario()['datos_personales_id'];
        $paciente = (new Epaciente())->buscarDetalle($pacienteId) ?? throw new ExcepcionNoEncontrado('No encontramos tu registro de paciente.');
        return (new Vficha_medica())->mostrar(
            $paciente,
            (new Eficha_medica())->conResponsable($pacienteId),
            (new Econsulta())->dePaciente($pacienteId, 5),
            false,
            true
        );
    }

    /** Pasos 4–5: valida, guarda con fecha y responsable, y lo anota en la bitácora. */
    public function actualizar(string $id): Respuesta
    {
        // E3: el paciente no edita su historia.
        if ($this->usuario()['perfil'] === 'paciente') {
            throw new ExcepcionAcceso('El paciente solo puede consultar su historia clínica.');
        }
        $paciente = $this->pacienteAccesible($id);
        $reglas = ['tipo_sangre' => 'opcional|en:' . implode(',', Eficha_medica::TIPOS_SANGRE)]   // E2
            + array_fill_keys(array_keys(Eficha_medica::CAMPOS_TEXTO), 'opcional|max:2000');
        $datos = array_map(static fn ($valor): string => trim((string) $valor), $this->validar($reglas) + array_fill_keys(array_keys($reglas), ''));

        (new Eficha_medica())->guardar((int) $paciente['id'], $datos, (int) $this->usuario()['id']);
        Bitacora::registrar('actualizar', 'ficha_medica', (int) $paciente['id']);
        return $this->redirigir('/pacientes/' . $paciente['id'] . '/historia', 'exito', 'Historia clínica de ' . $paciente['nombres'] . ' actualizada.');
    }

    /** Paso 2 y E1: el médico solo accede a pacientes que tienen una cita con él hoy. */
    private function pacienteAccesible(string $id): array
    {
        $paciente = (new Epaciente())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('El paciente no existe.');
        $usuario = $this->usuario();
        if ($usuario['perfil'] === 'medico' && !(new Ecita_medica())->medicoAtiendeHoy((int) $usuario['datos_personales_id'], (int) $paciente['id'])) {
            throw new ExcepcionAcceso('Solo puedes ver la historia clínica de pacientes que tienen cita contigo hoy.');
        }
        return $paciente;
    }
}
