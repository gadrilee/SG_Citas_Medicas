-- =====================================================================
-- 004 · Datos de demostración (NO cargar en una instalación real)
-- Contraseña de todas las cuentas: VidaSalud2026
--   admin · rosa.ag · dr.perez · dra.suarez · dr.gutierrez · dra.mendez · dr.antelo
--   maria.lopez · jose.flores · pedro.mamani · lucia.vargas
-- Fechas: octubre de 2026 (citas pasadas) y noviembre de 2026 (citas futuras), hora de Bolivia.
-- =====================================================================

BEGIN;

-- ---------- Personas ----------
INSERT INTO datos_personales (id, ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion) VALUES
    (1,  '4512367',    'Andrea',          'Cortez Suárez',         '1988-03-14', 'Femenino',  '70011223', 'andrea.cortez@vidasalud.bo', 'Av. Banzer, 4to anillo'),
    (2,  '5823411',    'Rosa',            'Aguilera Paz',          '1992-07-02', 'Femenino',  '70022334', 'rosa.aguilera@vidasalud.bo', 'B/ Equipetrol, calle 3'),
    (3,  '3921456',    'Juan Carlos',     'Pérez Rojas',           '1975-01-20', 'Masculino', '71133445', 'jc.perez@vidasalud.bo',      'Av. San Martín 220'),
    (4,  '4785123',    'María Fernanda',  'Suárez Vaca',           '1983-09-11', 'Femenino',  '71244556', 'mf.suarez@vidasalud.bo',     'B/ Las Palmas, calle 7'),
    (5,  '3302871',    'Luis Alberto',    'Gutiérrez Arce',        '1970-05-30', 'Masculino', '71355667', 'la.gutierrez@vidasalud.bo',  'Av. Beni 1450'),
    (6,  '5124789',    'Carla Andrea',    'Méndez Justiniano',     '1986-12-08', 'Femenino',  '71466778', 'ca.mendez@vidasalud.bo',     'Calle Ñuflo de Chávez 85'),
    (7,  '4012998',    'Ricardo',         'Antelo Banzer',         '1979-04-17', 'Masculino', '71577889', 'r.antelo@vidasalud.bo',      'Av. Piraí 3er anillo'),
    (8,  '7845123 SC', 'María',           'López Ribera',          '1992-02-25', 'Femenino',  '70012345', 'maria.lopez@correo.bo',      'B/ Hamacas, calle 12'),
    (9,  '6523411',    'José Antonio',    'Flores Cuéllar',        '1959-08-03', 'Masculino', '76543210', 'jose.flores@correo.bo',      'Av. Grigotá 3500'),
    (10, '9871234',    'Valentina',       'Rojas Saucedo',         '2018-06-21', 'Femenino',  '72233445', NULL,                         'B/ Urbarí, calle 4'),
    (11, '5432198 LP', 'Pedro',           'Mamani Quispe',         '1981-10-09', 'Masculino', '73344556', 'pedro.mamani@correo.bo',     'Plan 3000, UV 160'),
    (12, '8123456',    'Lucía',           'Vargas Melgar',         '1997-03-15', 'Femenino',  '74455667', 'lucia.vargas@correo.bo',     'Av. Santos Dumont 6to anillo'),
    (13, '6789012',    'Jorge',           'Paz Montaño',           '1990-11-27', 'Masculino', '75566778', NULL,                         'B/ Villa 1ro de Mayo'),
    (14, '7012345',    'Camila',          'Ortiz Egüez',           '2001-01-05', 'Femenino',  '76677889', NULL,                         'Av. Paraguá 2do anillo'),
    (15, '5567890',    'Diego',           'Rivero Salvatierra',    '1986-09-19', 'Masculino', '77788990', NULL,                         'B/ Sirari, calle Los Pinos');

INSERT INTO personal (id, fecha_ingreso) VALUES (1, '2024-02-01'), (2, '2025-01-15');

INSERT INTO medico (id, codigo, nro_registro_profesional) VALUES
    (3, 'M-001', 'MP-4521'), (4, 'M-002', 'MP-3874'), (5, 'M-003', 'MP-2910'),
    (6, 'M-004', 'MP-5133'), (7, 'M-005', 'OD-1288');

INSERT INTO paciente (id, codigo, fecha_registro) VALUES
    (8,  'P-0001', '2026-09-02 10:15-04'), (9,  'P-0002', '2026-09-05 09:00-04'),
    (10, 'P-0003', '2026-09-10 16:20-04'), (11, 'P-0004', '2026-09-18 11:40-04'),
    (12, 'P-0005', '2026-09-25 08:30-04'), (13, 'P-0006', '2026-10-01 15:10-04'),
    (14, 'P-0007', '2026-10-06 12:00-04'), (15, 'P-0008', '2026-10-09 17:45-04');

-- ---------- Cuentas (perfil: 1 admin, 2 recepción, 3 médico, 4 paciente) ----------
INSERT INTO usuario (id, datos_personales_id, perfil_id, nombre_usuario, contrasena_hash) VALUES
    (1,  1,  1, 'admin',        '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (2,  2,  2, 'rosa.ag',      '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (3,  3,  3, 'dr.perez',     '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (4,  4,  3, 'dra.suarez',   '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (5,  5,  3, 'dr.gutierrez', '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (6,  6,  3, 'dra.mendez',   '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (7,  7,  3, 'dr.antelo',    '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (8,  8,  4, 'maria.lopez',  '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (9,  9,  4, 'jose.flores',  '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (10, 11, 4, 'pedro.mamani', '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO'),
    (11, 12, 4, 'lucia.vargas', '$2y$10$KSCvUpNEp4IE8oUhVWOIu.z9KgWx7eumzaIKuj4/RQNScGXasEmvO');

INSERT INTO preferencia_cuenta (usuario_id, notificaciones, mostrar_contacto_al_medico)
SELECT id, TRUE, perfil_id = 4 FROM usuario;

-- ---------- Perfil clínico ----------
INSERT INTO ficha_medica (paciente_id, tipo_sangre, alergias, intolerancias_medicamentos, enfermedades_cronicas,
                          medicamentos_actuales, antecedentes_personales, antecedentes_familiares, fecha_actualizacion, actualizada_por) VALUES
    (8,  'O+', 'Penicilina', NULL, NULL, NULL, 'Apendicectomía (2010)', 'Madre con diabetes tipo 2', '2026-10-05 08:20-04', 3),
    (9,  'A+', NULL, 'Ibuprofeno (gastritis)', 'Hipertensión arterial', 'Losartán 50 mg cada 24 h', NULL, 'Padre con cardiopatía isquémica', '2026-10-05 09:50-04', 5),
    (10, 'B+', 'Polen', NULL, NULL, NULL, 'Vacunas al día', NULL, '2026-09-10 16:25-04', 2),
    (11, 'O-', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-07 08:35-04', 3),
    (12, 'AB+', NULL, NULL, NULL, 'Anticonceptivo oral', NULL, NULL, '2026-09-25 08:35-04', 2),
    (13, NULL, NULL, NULL, 'Asma leve', 'Salbutamol inhalado a demanda', NULL, NULL, '2026-10-01 15:15-04', 2),
    (14, 'A-', 'Mariscos', NULL, NULL, NULL, NULL, NULL, '2026-10-06 12:05-04', 2),
    (15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-09 17:50-04', 2);

INSERT INTO contacto_emergencia (paciente_id, nombre, parentesco, telefono) VALUES
    (8,  'Carlos López Ribera',  'Hermano', '70098765'),
    (9,  'Rosa Cuéllar de Flores', 'Esposa', '76501234'),
    (10, 'Ana Saucedo Pinto',    'Madre',   '72299887'),
    (10, 'Marco Rojas Vaca',     'Padre',   '72288776'),
    (11, 'Julia Quispe Mamani',  'Esposa',  '73355443');

-- ---------- Especialidades y agenda ----------
INSERT INTO medico_especialidad (medico_id, especialidad_id) VALUES
    (3, 1), (4, 2), (5, 3), (5, 1), (6, 4), (7, 5);

-- dia_semana ISO: 1 lunes … 7 domingo. Turnos de 15 minutos.
INSERT INTO horario_atencion (medico_id, dia_semana, hora_inicio, hora_fin, duracion_cita_min, vigente_desde) VALUES
    (3, 1, '08:00', '12:00', 15, '2026-10-01'), (3, 3, '08:00', '12:00', 15, '2026-10-01'), (3, 5, '14:00', '18:00', 15, '2026-10-01'),
    (4, 2, '14:00', '18:00', 15, '2026-10-01'), (4, 4, '14:00', '18:00', 15, '2026-10-01'),
    (5, 1, '09:00', '13:00', 15, '2026-10-01'), (5, 4, '08:00', '12:00', 15, '2026-10-01'),
    (6, 2, '08:00', '12:00', 15, '2026-10-01'), (6, 5, '08:00', '12:00', 15, '2026-10-01'),
    (7, 1, '14:00', '18:00', 15, '2026-10-01'), (7, 3, '14:00', '18:00', 15, '2026-10-01');

INSERT INTO bloqueo_agenda (medico_id, fecha, motivo) VALUES
    (3, '2026-11-20', 'Congreso médico nacional'),
    (6, '2026-11-13', 'Capacitación');

-- ---------- Citas (estado: 1 programada, 2 confirmada, 3 atendida, 4 cancelada, 5 inasistencia) ----------
-- Importe según especialidad: Medicina general 120, Pediatría 150, Geriatría 150, Ginecología 180, Odontología 140.
INSERT INTO cita_medica (id, codigo, paciente_id, medico_id, especialidad_id, estado_cita_id, inicio, fin, motivo, modalidad, importe_acordado, creada_por, fecha_creacion) VALUES
    (1,  'CM-0001', 8,  3, 1, 3, '2026-10-05 08:00-04', '2026-10-05 08:15-04', 'Dolor de garganta',            'presencial', 120, 8,  '2026-10-01 19:20-04'),
    (2,  'CM-0002', 9,  5, 3, 3, '2026-10-05 09:30-04', '2026-10-05 09:45-04', 'Control de presión arterial',  'presencial', 150, 2,  '2026-10-02 09:10-04'),
    (3,  'CM-0003', 11, 3, 1, 3, '2026-10-07 08:15-04', '2026-10-07 08:30-04', 'Fiebre y malestar general',    'presencial', 120, 2,  '2026-10-06 10:00-04'),
    (4,  'CM-0004', 12, 6, 4, 4, '2026-10-13 11:00-04', '2026-10-13 11:15-04', 'Control anual',                'presencial', 180, 11, '2026-10-08 20:30-04'),
    (5,  'CM-0005', 9,  7, 5, 5, '2026-10-14 16:00-04', '2026-10-14 16:15-04', 'Dolor de muela',               'presencial', 140, 9,  '2026-10-10 18:00-04'),
    (6,  'CM-0006', 10, 4, 2, 3, '2026-10-15 15:00-04', '2026-10-15 15:15-04', 'Control de niño sano',         'presencial', 150, 2,  '2026-10-09 11:30-04'),
    (7,  'CM-0007', 13, 3, 1, 3, '2026-10-19 09:00-04', '2026-10-19 09:15-04', 'Dolor abdominal',              'presencial', 120, 2,  '2026-10-16 08:45-04'),
    (8,  'CM-0008', 14, 6, 4, 3, '2026-10-20 08:30-04', '2026-10-20 08:45-04', 'Consulta ginecológica',        'presencial', 180, 2,  '2026-10-15 14:00-04'),
    (9,  'CM-0009', 15, 7, 5, 4, '2026-10-21 14:00-04', '2026-10-21 14:15-04', 'Limpieza dental',              'presencial', 140, 2,  '2026-10-17 10:20-04'),
    (10, 'CM-0010', 8,  5, 1, 5, '2026-10-22 08:00-04', '2026-10-22 08:15-04', 'Resultados de laboratorio',    'presencial', 120, 8,  '2026-10-18 21:10-04'),
    (11, 'CM-0011', 8,  3, 1, 2, '2026-11-16 09:00-04', '2026-11-16 09:15-04', 'Control de garganta',          'presencial', 120, 8,  '2026-10-20 19:00-04'),
    (12, 'CM-0012', 9,  5, 3, 1, '2026-11-16 10:30-04', '2026-11-16 10:45-04', 'Control de presión arterial',  'presencial', 150, 2,  '2026-10-21 09:30-04'),
    (13, 'CM-0013', 10, 4, 2, 1, '2026-11-17 15:00-04', '2026-11-17 15:15-04', 'Vacunación y control',         'presencial', 150, 2,  '2026-10-21 10:15-04'),
    (14, 'CM-0014', 11, 3, 1, 1, '2026-11-16 09:15-04', '2026-11-16 09:30-04', 'Control post tratamiento',     'presencial', 120, 10, '2026-10-21 20:40-04'),
    (15, 'CM-0015', 12, 6, 4, 1, '2026-11-17 08:00-04', '2026-11-17 08:15-04', 'Control anual',                'presencial', 180, 11, '2026-10-19 13:00-04'),
    (16, 'CM-0016', 13, 7, 5, 1, '2026-11-18 14:30-04', '2026-11-18 14:45-04', 'Revisión dental',              'presencial', 140, 2,  '2026-10-22 09:00-04'),
    (17, 'CM-0017', 14, 5, 1, 2, '2026-11-19 08:45-04', '2026-11-19 09:00-04', 'Chequeo general',              'virtual',    120, 2,  '2026-10-22 11:20-04'),
    (18, 'CM-0018', 15, 3, 1, 1, '2026-11-18 08:00-04', '2026-11-18 08:15-04', 'Dolor de espalda',             'presencial', 120, 2,  '2026-10-22 16:05-04'),
    (19, 'CM-0019', 9,  3, 1, 1, '2026-11-23 10:00-04', '2026-11-23 10:15-04', 'Certificado médico',           'presencial', 120, 9,  '2026-10-22 18:30-04'),
    (20, 'CM-0020', 8,  6, 4, 4, '2026-11-24 09:00-04', '2026-11-24 09:15-04', 'Control ginecológico',         'presencial', 180, 8,  '2026-10-20 19:10-04'),
    (21, 'CM-0021', 12, 6, 4, 1, '2026-11-24 09:00-04', '2026-11-24 09:15-04', 'Consulta ginecológica',        'presencial', 180, 11, '2026-10-22 12:00-04');

-- ---------- Historial: evento de creación de cada cita ----------
INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, fecha_hora)
SELECT id, creada_por, 'creacion', NULL, 1, inicio, fin, fecha_creacion
  FROM cita_medica WHERE codigo <> 'CM-0015';

-- CM-0015 se creó para el 10/11 y luego se reprogramó al 17/11.
INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_anterior, fin_anterior, inicio_nuevo, fin_nuevo, observacion, fecha_hora) VALUES
    (15, 11, 'creacion', NULL, 1, NULL, NULL, '2026-11-10 10:00-04', '2026-11-10 10:15-04', NULL, '2026-10-19 13:00-04'),
    (15, 11, 'reprogramacion', 1, 1, '2026-11-10 10:00-04', '2026-11-10 10:15-04', '2026-11-17 08:00-04', '2026-11-17 08:15-04', 'Viaje de trabajo esa semana', '2026-10-21 18:20-04');

-- Cambios de estado posteriores a la creación.
INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, observacion, fecha_hora)
SELECT c.id, e.usuario_id, 'cambio_estado', e.anterior, e.nuevo, c.inicio, c.fin, e.observacion, e.fecha_hora::timestamptz
  FROM (VALUES
    ('CM-0001', 2, 1, 2, NULL,                         '2026-10-04 17:00-04'),
    ('CM-0001', 3, 2, 3, NULL,                         '2026-10-05 08:16-04'),
    ('CM-0002', 5, 1, 3, NULL,                         '2026-10-05 09:47-04'),
    ('CM-0003', 3, 1, 3, NULL,                         '2026-10-07 08:31-04'),
    ('CM-0004', 11, 1, 4, 'Viaje fuera de la ciudad',  '2026-10-11 09:00-04'),
    ('CM-0005', 2, 1, 5, NULL,                         '2026-10-14 16:30-04'),
    ('CM-0006', 4, 1, 3, NULL,                         '2026-10-15 15:16-04'),
    ('CM-0007', 3, 1, 3, NULL,                         '2026-10-19 09:17-04'),
    ('CM-0008', 6, 1, 3, NULL,                         '2026-10-20 08:46-04'),
    ('CM-0009', 2, 1, 4, 'El paciente llamó para cancelar', '2026-10-20 15:00-04'),
    ('CM-0010', 2, 1, 5, NULL,                         '2026-10-22 08:30-04'),
    ('CM-0011', 2, 1, 2, NULL,                         '2026-10-22 10:00-04'),
    ('CM-0017', 2, 1, 2, NULL,                         '2026-10-22 11:25-04'),
    ('CM-0020', 8, 1, 4, 'Cambio de especialista',     '2026-10-21 08:00-04')
  ) AS e (codigo, usuario_id, anterior, nuevo, observacion, fecha_hora)
  JOIN cita_medica c ON c.codigo = e.codigo;

-- ---------- Atención clínica de las citas atendidas ----------
INSERT INTO consulta (id, cita_id, fecha_hora, observaciones, diagnostico) VALUES
    (1, 1, '2026-10-05 08:05-04', 'Odinofagia de 3 días, amígdalas eritematosas sin exudado.', 'Faringitis aguda'),
    (2, 2, '2026-10-05 09:35-04', 'PA 150/95 mmHg. Refiere cefalea matutina ocasional.',      'Hipertensión arterial no controlada'),
    (3, 3, '2026-10-07 08:20-04', 'Temperatura 38,4 °C, mialgias, sin signos de alarma.',      'Síndrome gripal'),
    (4, 6, '2026-10-15 15:05-04', 'Peso y talla adecuados para la edad. Esquema de vacunas al día.', 'Niña sana'),
    (5, 7, '2026-10-19 09:05-04', 'Dolor en epigastrio postprandial de 2 semanas.',            'Dispepsia funcional'),
    (6, 8, '2026-10-20 08:35-04', 'Control ginecológico de rutina sin hallazgos.',             'Examen ginecológico normal');

INSERT INTO tratamiento (consulta_id, indicaciones, fecha_inicio, fecha_fin) VALUES
    (1, 'Ibuprofeno 400 mg cada 8 horas por 3 días; abundantes líquidos.', '2026-10-05', '2026-10-08'),
    (2, 'Losartán 50 mg cada 24 horas; dieta hiposódica y control en 4 semanas.', '2026-10-05', NULL),
    (3, 'Paracetamol 500 mg cada 6 horas si hay fiebre; reposo por 48 horas.', '2026-10-07', '2026-10-09'),
    (5, 'Omeprazol 20 mg en ayunas por 14 días; evitar comidas irritantes.', '2026-10-19', '2026-11-02');

INSERT INTO estudio_laboratorio (consulta_id, nombre_estudio, indicaciones, solicitado_en, estado, resultado_texto, publicado_en, publicado_por) VALUES
    (2, 'Hemograma completo', 'En ayunas de 8 horas', '2026-10-05 09:40-04', 'disponible',
        'Hemoglobina 14,2 g/dL; leucocitos 6.800/mm³; plaquetas 245.000/mm³. Valores dentro de rango.', '2026-10-07 11:00-04', 5),
    (2, 'Perfil lipídico',    'En ayunas de 12 horas', '2026-10-05 09:40-04', 'en_proceso', NULL, NULL, NULL),
    (5, 'Examen general de orina', 'Primera orina de la mañana', '2026-10-19 09:10-04', 'pendiente', NULL, NULL, NULL),
    (5, 'Ecografía abdominal', NULL, '2026-10-19 09:10-04', 'pendiente', NULL, NULL, NULL);

-- ---------- Pagos (pasarela simulada) ----------
INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en, verificado_en) VALUES
    (1,  1, 8, gen_random_uuid(), 'VP-000101', 120, 'BOB', 'aprobado',  '2026-10-01 19:25-04', '2026-10-01 19:26-04'),
    (2,  1, 2, gen_random_uuid(), 'VP-000102', 150, 'BOB', 'aprobado',  '2026-10-05 09:50-04', '2026-10-05 09:51-04'),
    (11, 1, 8, gen_random_uuid(), 'VP-000110', 120, 'BOB', 'rechazado', '2026-10-20 19:05-04', '2026-10-20 19:06-04'),
    (11, 1, 8, gen_random_uuid(), 'VP-000111', 120, 'BOB', 'aprobado',  '2026-10-20 19:08-04', '2026-10-20 19:09-04'),
    (12, 1, 2, gen_random_uuid(), NULL,        150, 'BOB', 'pendiente', '2026-10-21 09:35-04', NULL),
    (20, 1, 8, gen_random_uuid(), 'VP-000120', 180, 'BOB', 'reembolsado', '2026-10-20 19:15-04', '2026-10-21 08:30-04');

-- ---------- Bitácora ----------
INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip) VALUES
    (1, 'crear',            'usuario',             2,  '2025-01-15 09:00-04', '192.168.1.10'),
    (2, 'registrar',        'paciente',            9,  '2026-09-05 09:00-04', '192.168.1.21'),
    (8, 'reservar',         'cita_medica',         1,  '2026-10-01 19:20-04', '181.115.20.4'),
    (5, 'registrar',        'consulta',            2,  '2026-10-05 09:47-04', '192.168.1.35'),
    (5, 'publicar',         'estudio_laboratorio', 1,  '2026-10-07 11:00-04', '192.168.1.35'),
    (11, 'cancelar',        'cita_medica',         4,  '2026-10-11 09:00-04', '181.115.33.7'),
    (11, 'reprogramar',     'cita_medica',         15, '2026-10-21 18:20-04', '181.115.33.7'),
    (2, 'reservar',         'cita_medica',         12, '2026-10-21 09:30-04', '192.168.1.21'),
    (2, 'confirmar',        'cita_medica',         11, '2026-10-22 10:00-04', '192.168.1.21'),
    (1, 'iniciar_sesion',   'usuario',             1,  '2026-10-22 18:00-04', '192.168.1.10');

-- Sincroniza las secuencias de identidad con los ids explícitos.
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
