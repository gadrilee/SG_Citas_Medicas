<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Asignación de privilegios a un perfil (PK compuesta). Se activa/desactiva en lugar de borrar. */
final class Eperfil_privilegio extends Modelo
{
    protected const TABLA = 'perfil_privilegio';

    /** Deja activos exactamente los privilegios indicados para el perfil. */
    public function asignar(int $perfilId, array $privilegioIds): void
    {
        $this->ejecutar('UPDATE perfil_privilegio SET activo = FALSE WHERE perfil_id = ?', [$perfilId]);
        $consulta = $this->bd->prepare(
            'INSERT INTO perfil_privilegio (perfil_id, privilegio_id) VALUES (?, ?)
             ON CONFLICT (perfil_id, privilegio_id) DO UPDATE SET activo = TRUE, fecha_asignacion = CURRENT_TIMESTAMP'
        );
        foreach ($privilegioIds as $privilegioId) {
            $consulta->execute([$perfilId, (int) $privilegioId]);
        }
    }
}
