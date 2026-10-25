-- =====================================================================
-- 003 · Catálogos fijos y privilegios por perfil
-- Datos necesarios para operar (también en producción). No incluye datos de demo.
-- =====================================================================

BEGIN;

INSERT INTO perfil (id, nombre, descripcion) VALUES
    (1, 'administrador', 'Configura el sistema, usuarios, médicos, agenda, bitácora y reportes'),
    (2, 'recepcionista', 'Gestiona pacientes, reservas, asistencia y cobros'),
    (3, 'medico',        'Consulta su agenda y registra la atención de sus pacientes del día'),
    (4, 'paciente',      'Reserva y consulta sus propias citas, historia y resultados');

INSERT INTO estado_cita (id, nombre, descripcion) VALUES
    (1, 'programada',   'Cita registrada'),
    (2, 'confirmada',   'Asistencia confirmada'),
    (3, 'atendida',     'Consulta realizada'),
    (4, 'cancelada',    'Cita cancelada; no ocupa el horario'),
    (5, 'inasistencia', 'El paciente no se presentó');

INSERT INTO especialidad (id, nombre, descripcion) VALUES
    (1, 'Medicina general', 'Atención primaria para adultos y control preventivo.'),
    (2, 'Pediatría',        'Control de niño sano y atención de niños y adolescentes.'),
    (3, 'Geriatría',        'Atención integral de personas adultas mayores.'),
    (4, 'Ginecología',      'Salud de la mujer y control ginecológico.'),
    (5, 'Odontología',      'Prevención, diagnóstico y tratamiento dental.');

INSERT INTO pasarela_pago (id, codigo, nombre) VALUES
    (1, 'simulada', 'Vida Pay (pasarela simulada)');

-- Privilegios: uno por operación de los casos de uso (docs/casos_uso.js).
INSERT INTO privilegio (nombre, descripcion) VALUES
    ('usuarios.gestionar',       'CU02 · Crear, editar y desactivar cuentas'),
    ('perfiles.gestionar',       'CU02 · Asignar privilegios a los perfiles'),
    ('bitacora.consultar',       'CU03 · Consultar la bitácora'),
    ('pacientes.gestionar',      'CU04 · Registrar y actualizar pacientes'),
    ('medicos.gestionar',        'CU05 · Registrar y actualizar médicos'),
    ('especialidades.gestionar', 'CU06 · Mantener el catálogo de especialidades'),
    ('horarios.gestionar',       'CU07 · Configurar horarios y bloqueos'),
    ('disponibilidad.consultar', 'CU08 · Consultar cupos libres'),
    ('citas.reservar',           'CU09 · Reservar citas'),
    ('citas.reprogramar',        'CU10 · Reprogramar citas'),
    ('citas.cancelar',           'CU11 · Cancelar citas'),
    ('asistencia.registrar',     'CU12 · Registrar confirmación e inasistencia'),
    ('agenda.consultar',         'CU13 · Consultar la agenda médica'),
    ('historial.consultar',      'CU14 · Consultar el historial de citas'),
    ('reportes.generar',         'CU15 · Generar reportes administrativos'),
    ('historia.consultar',       'CU16 · Consultar la historia clínica'),
    ('historia.actualizar',      'CU16 · Actualizar la historia clínica'),
    ('consultas.registrar',      'CU17 · Registrar consulta y tratamiento'),
    ('estudios.solicitar',       'CU18 · Solicitar estudios de laboratorio'),
    ('resultados.publicar',      'CU19 · Publicar resultados de laboratorio'),
    ('resultados.consultar',     'CU19 · Consultar resultados publicados'),
    ('pagos.gestionar',          'CU20 · Iniciar, verificar y devolver pagos');

-- Asignación por perfil. El acceso de cada registro (p. ej. el paciente solo ve lo suyo y el
-- médico solo a sus pacientes del día) lo controla además cada caso de uso.
INSERT INTO perfil_privilegio (perfil_id, privilegio_id)
SELECT p.perfil_id, pr.id
  FROM (VALUES
    (1, 'usuarios.gestionar'), (1, 'perfiles.gestionar'), (1, 'bitacora.consultar'),
    (1, 'medicos.gestionar'), (1, 'especialidades.gestionar'), (1, 'horarios.gestionar'),
    (1, 'agenda.consultar'), (1, 'historial.consultar'), (1, 'reportes.generar'),

    (2, 'pacientes.gestionar'), (2, 'disponibilidad.consultar'), (2, 'citas.reservar'),
    (2, 'citas.reprogramar'), (2, 'citas.cancelar'), (2, 'asistencia.registrar'),
    (2, 'agenda.consultar'), (2, 'historial.consultar'), (2, 'historia.consultar'),
    (2, 'historia.actualizar'), (2, 'pagos.gestionar'),

    (3, 'agenda.consultar'), (3, 'historia.consultar'), (3, 'historia.actualizar'),
    (3, 'consultas.registrar'), (3, 'estudios.solicitar'), (3, 'resultados.publicar'),
    (3, 'resultados.consultar'),

    (4, 'disponibilidad.consultar'), (4, 'citas.reservar'), (4, 'citas.reprogramar'),
    (4, 'citas.cancelar'), (4, 'historial.consultar'), (4, 'historia.consultar'),
    (4, 'resultados.consultar'), (4, 'pagos.gestionar')
  ) AS p (perfil_id, privilegio)
  JOIN privilegio pr ON pr.nombre = p.privilegio;

-- Sincroniza las secuencias de identidad con los ids insertados explícitamente.
DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT table_name, column_name FROM information_schema.columns
              WHERE table_schema = 'public' AND is_identity = 'YES'
    LOOP
        EXECUTE format('SELECT setval(pg_get_serial_sequence(%L, %L), COALESCE((SELECT max(%I) FROM %I), 0) + 1, false)',
                       r.table_name, r.column_name, r.column_name, r.table_name);
    END LOOP;
END $$;

COMMIT;
