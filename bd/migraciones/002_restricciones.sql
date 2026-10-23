-- =====================================================================
-- 002 · Restricciones e índices que completan bd/clinica_vida_salud.sql (001)
-- Activa lo que el script inicial dejó comentado y corrige las brechas
-- encontradas en docs/REVISION_BD.md.
-- =====================================================================

BEGIN;

-- ---------- Agenda: un médico no puede tener citas superpuestas (RF07 / RN07) ----------
-- Las citas canceladas (estado 4) no ocupan el horario (RN09).
CREATE EXTENSION IF NOT EXISTS btree_gist;

ALTER TABLE cita_medica ADD CONSTRAINT ex_cita_sin_solapamiento
    EXCLUDE USING gist (medico_id WITH =, tstzrange(inicio, fin, '[)') WITH &&)
    WHERE (estado_cita_id <> 4);

-- ---------- Pagos: un solo intento pendiente o aprobado por cita ----------
CREATE UNIQUE INDEX ux_pago_cita_activo
    ON pago (cita_id) WHERE estado IN ('pendiente', 'aprobado');

-- ---------- Catálogos con significado fijo ----------
-- El código usa estos identificadores (cancelada = 4 en la exclusión y en historial_cita).
ALTER TABLE estado_cita ADD CONSTRAINT ck_estado_cita_catalogo CHECK (
    (id, nombre) IN ((1, 'programada'), (2, 'confirmada'), (3, 'atendida'), (4, 'cancelada'), (5, 'inasistencia'))
);
ALTER TABLE perfil ADD CONSTRAINT ck_perfil_catalogo CHECK (
    (id, nombre) IN ((1, 'administrador'), (2, 'recepcionista'), (3, 'medico'), (4, 'paciente'))
);

-- ---------- Horarios: la ventana debe dividirse en turnos completos ----------
ALTER TABLE horario_atencion ADD CONSTRAINT ck_horario_turnos_completos CHECK (
    (EXTRACT(EPOCH FROM (hora_fin - hora_inicio))::INT / 60) % duracion_cita_min = 0
);

-- ---------- Datos personales: rango razonable de fecha de nacimiento ----------
-- "No futura" depende del reloj y se valida en la aplicación (Validador: fecha_no_futura).
ALTER TABLE datos_personales ADD CONSTRAINT ck_dp_fecha_nacimiento CHECK (fecha_nacimiento >= DATE '1900-01-01');

-- ---------- Historial: una reprogramación solo ocurre en citas vigentes ----------
ALTER TABLE historial_cita ADD CONSTRAINT ck_hist_reprogramacion_vigente CHECK (
    tipo_evento <> 'reprogramacion' OR estado_nuevo_id IN (1, 2)
);

-- ---------- Índices para las consultas frecuentes ----------
CREATE INDEX ix_cita_medico_inicio      ON cita_medica (medico_id, inicio);
CREATE INDEX ix_cita_paciente_inicio    ON cita_medica (paciente_id, inicio DESC);
CREATE INDEX ix_cita_estado_inicio      ON cita_medica (estado_cita_id, inicio);
CREATE INDEX ix_historial_cita          ON historial_cita (cita_id, fecha_hora);
CREATE INDEX ix_horario_medico_dia      ON horario_atencion (medico_id, dia_semana) WHERE activo;
CREATE INDEX ix_contacto_paciente       ON contacto_emergencia (paciente_id);
CREATE INDEX ix_tratamiento_consulta    ON tratamiento (consulta_id);
CREATE INDEX ix_estudio_consulta        ON estudio_laboratorio (consulta_id);
CREATE INDEX ix_estudio_estado          ON estudio_laboratorio (estado);
CREATE INDEX ix_pago_cita               ON pago (cita_id);
CREATE INDEX ix_bitacora_fecha          ON bitacora (fecha_hora DESC);
CREATE INDEX ix_bitacora_usuario        ON bitacora (usuario_id, fecha_hora DESC);
CREATE INDEX ix_dp_apellidos_nombres    ON datos_personales (lower(apellidos), lower(nombres));
CREATE INDEX ix_usuario_perfil          ON usuario (perfil_id);

COMMIT;
