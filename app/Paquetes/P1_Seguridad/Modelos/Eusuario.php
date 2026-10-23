<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P1_Seguridad\Modelos;

use VidaSalud\Nucleo\Modelo;

/** Cuenta de acceso: una por persona, con un perfil y su contraseña como hash. */
final class Eusuario extends Modelo
{
    protected const TABLA = 'usuario';

    private const DETALLE = 'SELECT u.id, u.datos_personales_id, u.perfil_id, u.nombre_usuario, u.contrasena_hash, u.activo, u.fecha_registro,
                                    pf.nombre AS perfil, dp.nombres, dp.apellidos, dp.ci, dp.correo,
                                    m.codigo AS codigo_medico, pa.codigo AS codigo_paciente,
                                    (m.id IS NOT NULL) AS es_medico, (pa.id IS NOT NULL) AS es_paciente, (pe.id IS NOT NULL) AS es_personal
                               FROM usuario u
                               JOIN perfil pf ON pf.id = u.perfil_id
                               JOIN datos_personales dp ON dp.id = u.datos_personales_id
                               LEFT JOIN medico m ON m.id = dp.id
                               LEFT JOIN paciente pa ON pa.id = dp.id
                               LEFT JOIN personal pe ON pe.id = dp.id';

    /** CU01: busca por nombre de usuario o por correo de la persona. */
    public function buscarParaAcceso(string $usuarioOCorreo): ?array
    {
        return $this->primero(
            self::DETALLE . ' WHERE lower(btrim(u.nombre_usuario)) = lower(btrim(:u1)) OR lower(btrim(dp.correo)) = lower(btrim(:u2)) LIMIT 1',
            ['u1' => $usuarioOCorreo, 'u2' => $usuarioOCorreo]
        );
    }

    public function buscarDetalle(int $id): ?array
    {
        return $this->primero(self::DETALLE . ' WHERE u.id = ?', [$id]);
    }

    public function registrar(int $personaId, int $perfilId, string $nombreUsuario, string $contrasena): int
    {
        return $this->insertar([
            'datos_personales_id' => $personaId,
            'perfil_id'           => $perfilId,
            'nombre_usuario'      => mb_strtolower(trim($nombreUsuario)),
            'contrasena_hash'     => password_hash($contrasena, PASSWORD_DEFAULT),
        ]);
    }

    public function listar(string $buscar, ?int $perfilId, ?bool $activo, int $pagina): array
    {
        $condiciones = ['TRUE'];
        $parametros = [];
        if ($buscar !== '') {
            $condiciones[] = "(u.nombre_usuario ILIKE :b1 OR (dp.nombres || ' ' || dp.apellidos) ILIKE :b2)";
            $parametros += ['b1' => "%$buscar%", 'b2' => "%$buscar%"];
        }
        if ($perfilId) {
            $condiciones[] = 'u.perfil_id = :perfil';
            $parametros['perfil'] = $perfilId;
        }
        if ($activo !== null) {
            $condiciones[] = 'u.activo = :activo';
            $parametros['activo'] = $activo ? 'true' : 'false';
        }
        return $this->paginar(
            "u.id, u.nombre_usuario, u.perfil_id, u.activo, u.fecha_registro, pf.nombre AS perfil, dp.nombres || ' ' || dp.apellidos AS persona",
            'FROM usuario u JOIN perfil pf ON pf.id = u.perfil_id JOIN datos_personales dp ON dp.id = u.datos_personales_id WHERE ' . implode(' AND ', $condiciones),
            $parametros,
            'u.activo DESC, pf.id, u.nombre_usuario',
            $pagina
        );
    }

    /** Personas registradas que aún no tienen cuenta (para "Nueva cuenta" de CU02). */
    public function personasSinCuenta(): array
    {
        return $this->consultar(
            "SELECT dp.id, dp.nombres || ' ' || dp.apellidos AS nombre, dp.ci,
                    CASE WHEN m.id IS NOT NULL THEN 'Médico' WHEN pe.id IS NOT NULL THEN 'Personal' WHEN p.id IS NOT NULL THEN 'Paciente' ELSE 'Persona' END AS tipo
               FROM datos_personales dp
               LEFT JOIN medico m ON m.id = dp.id
               LEFT JOIN personal pe ON pe.id = dp.id
               LEFT JOIN paciente p ON p.id = dp.id
              WHERE NOT EXISTS (SELECT 1 FROM usuario u WHERE u.datos_personales_id = dp.id)
              ORDER BY dp.apellidos, dp.nombres"
        );
    }

    public function existeNombre(string $nombreUsuario, ?int $excluirId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM usuario WHERE lower(btrim(nombre_usuario)) = lower(btrim(?)) AND id <> ?',
            [$nombreUsuario, $excluirId ?? 0]
        );
    }

    public function personaTieneCuenta(int $personaId): bool
    {
        return (bool) $this->valor('SELECT 1 FROM usuario WHERE datos_personales_id = ?', [$personaId]);
    }

    public function cambiarContrasena(int $id, string $contrasena): void
    {
        $this->ejecutar('UPDATE usuario SET contrasena_hash = ? WHERE id = ?', [password_hash($contrasena, PASSWORD_DEFAULT), $id]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE usuario SET activo = ? WHERE id = ?', [$activo ? 'true' : 'false', $id]);
    }

    public function contarAdministradoresActivos(): int
    {
        return (int) $this->valor('SELECT count(*) FROM usuario WHERE perfil_id = 1 AND activo');
    }
}
