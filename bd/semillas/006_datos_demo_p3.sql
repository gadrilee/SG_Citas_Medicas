-- =====================================================================
-- 006 · Datos de demostración para P3 (CU11–CU15). NO cargar en una instalación real.
-- Las fechas son RELATIVAS al día en que se ejecuta (hora de Bolivia), así el control de
-- asistencia, la agenda, el historial y los reportes siempre tienen datos:
--   · 3 semanas atrás: citas atendidas (con consulta y pago), canceladas e inasistencias.
--   · Hoy: turnos terminados (para confirmar o registrar inasistencia) y turnos por venir.
--   · 2 semanas adelante: programadas, confirmadas, algunas reprogramadas y canceladas.
-- Respeta los horarios y bloqueos de 004, no superpone citas del médico ni del paciente y
-- puede volver a ejecutarse: omite los turnos que ya tienen una cita.
-- Requiere 004_datos_demo.sql y 005_tarifa_especialidad.sql.
-- =====================================================================

BEGIN;

-- ---------- Pacientes nuevos del mes (reporte «Pacientes registrados») ----------
DO $$
DECLARE
    hoy date := (now() AT TIME ZONE 'America/La_Paz')::date;
    nuevo record;
    v_id bigint;
    v_numero int;
BEGIN
    FOR nuevo IN SELECT * FROM (VALUES
        ('9123456',  'Mateo',     'Rojas Saucedo',     DATE '2020-04-12', 'Masculino', '72233446', 'B/ Urbarí, calle 4',          20),
        ('9234567',  'Sofía',     'Mamani Ortiz',      DATE '2019-08-30', 'Femenino',  '73344557', 'Plan 3000, UV 162',           12),
        ('6345678',  'Hugo',      'Justiniano Vaca',   DATE '1955-02-18', 'Masculino', '77812345', 'Av. Alemana 4to anillo',       5),
        ('7456789',  'Gabriela',  'Suárez Peña',       DATE '1994-12-03', 'Femenino',  '76923456', 'B/ Equipetrol Norte',          1)
    ) AS t (ci, nombres, apellidos, nacimiento, sexo, telefono, direccion, dias_atras)
    LOOP
        CONTINUE WHEN EXISTS (SELECT 1 FROM datos_personales WHERE ci = nuevo.ci);
        INSERT INTO datos_personales (ci, nombres, apellidos, fecha_nacimiento, sexo, telefono, direccion)
        VALUES (nuevo.ci, nuevo.nombres, nuevo.apellidos, nuevo.nacimiento, nuevo.sexo, nuevo.telefono, nuevo.direccion)
        RETURNING id INTO v_id;
        SELECT COALESCE(max(substring(codigo FROM 3)::int), 0) + 1 INTO v_numero FROM paciente WHERE codigo ~ '^P-[0-9]+$';
        INSERT INTO paciente (id, codigo, fecha_registro)
        VALUES (v_id, 'P-' || lpad(v_numero::text, 4, '0'), ((hoy - nuevo.dias_atras) + TIME '10:30') AT TIME ZONE 'America/La_Paz');
        INSERT INTO ficha_medica (paciente_id, fecha_actualizacion, actualizada_por)
        VALUES (v_id, ((hoy - nuevo.dias_atras) + TIME '10:35') AT TIME ZONE 'America/La_Paz', 2);
        INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
        VALUES (2, 'registrar', 'paciente', v_id, ((hoy - nuevo.dias_atras) + TIME '10:30') AT TIME ZONE 'America/La_Paz', '192.168.1.21');
    END LOOP;
END $$;

-- ---------- Citas alrededor de hoy ----------
DO $$
DECLARE
    zona CONSTANT text := 'America/La_Paz';
    hoy date := (now() AT TIME ZONE 'America/La_Paz')::date;
    ahora timestamptz := now();
    adultos bigint[];
    ninos bigint[];
    motivos text[] := ARRAY[
        'Control de presión arterial', 'Dolor de cabeza frecuente', 'Chequeo general', 'Control de diabetes',
        'Dolor de garganta', 'Resultados de laboratorio', 'Dolor lumbar', 'Certificado médico',
        'Control post tratamiento', 'Malestar estomacal', 'Tos persistente', 'Revisión de rutina'];
    motivos_cancelacion text[] := ARRAY[
        'Viaje fuera de la ciudad', 'El paciente llamó para cancelar', 'Cruce con horario de trabajo',
        'Se siente mejor y no necesita la consulta', 'Problemas de transporte'];
    diagnosticos text[] := ARRAY[
        'Hipertensión arterial controlada', 'Cefalea tensional', 'Paciente sano', 'Diabetes tipo 2 compensada',
        'Faringitis aguda', 'Valores de laboratorio normales', 'Lumbalgia mecánica', 'Apto para actividad laboral',
        'Evolución favorable', 'Gastritis aguda', 'Bronquitis aguda', 'Examen físico normal'];
    d int;
    dia date;
    v record;
    turno int;
    turnos int[];
    k int := 0;
    n int;
    v_paciente bigint;
    v_inicio timestamptz;
    v_fin timestamptz;
    v_estado smallint;
    v_creador bigint;
    v_medico_usuario bigint;
    v_creada timestamptz;
    v_cita bigint;
    v_importe numeric(12,2);
    v_motivo text;
    v_reprogramada boolean;
    v_previo timestamptz;
BEGIN
    SELECT array_agg(p.id ORDER BY p.id) INTO adultos
      FROM paciente p JOIN datos_personales dp ON dp.id = p.id
     WHERE p.activo AND age(hoy, dp.fecha_nacimiento) >= INTERVAL '15 years';
    SELECT array_agg(p.id ORDER BY p.id) INTO ninos
      FROM paciente p JOIN datos_personales dp ON dp.id = p.id
     WHERE p.activo AND age(hoy, dp.fecha_nacimiento) < INTERVAL '15 years';
    SELECT COALESCE(max(substring(codigo FROM 4)::int), 0) INTO n FROM cita_medica WHERE codigo ~ '^CM-[0-9]+$';

    FOR d IN -21..14 LOOP
        dia := hoy + d;
        FOR v IN
            SELECT h.medico_id, h.hora_inicio, h.hora_fin, h.duracion_cita_min,
                   (SELECT me.especialidad_id FROM medico_especialidad me
                     WHERE me.medico_id = h.medico_id AND me.activo ORDER BY me.especialidad_id DESC LIMIT 1) AS especialidad_id
              FROM horario_atencion h
              JOIN medico m ON m.id = h.medico_id AND m.activo
             WHERE h.activo
               AND h.dia_semana = EXTRACT(ISODOW FROM dia)
               AND h.vigente_desde <= dia AND (h.vigente_hasta IS NULL OR h.vigente_hasta >= dia)
               AND NOT EXISTS (SELECT 1 FROM bloqueo_agenda b WHERE b.medico_id = h.medico_id AND b.fecha = dia)
             ORDER BY h.medico_id, h.hora_inicio
        LOOP
            turnos := CASE
                WHEN d = 0 THEN ARRAY[0, 1, 3, 5, 6, 8, 10, 12, 14]
                WHEN d BETWEEN -7 AND 7 THEN ARRAY[1, 4, 7, 11]
                ELSE ARRAY[2, 9] END;
            SELECT importe_consulta INTO v_importe FROM especialidad WHERE id = v.especialidad_id;
            SELECT id INTO v_medico_usuario FROM usuario WHERE datos_personales_id = v.medico_id;

            FOREACH turno IN ARRAY turnos LOOP
                v_inicio := (dia + v.hora_inicio) AT TIME ZONE zona + make_interval(mins => turno * v.duracion_cita_min);
                v_fin := v_inicio + make_interval(mins => v.duracion_cita_min);
                CONTINUE WHEN v_fin > (dia + v.hora_fin) AT TIME ZONE zona;
                -- El turno ya tiene una cita (por ejemplo, al volver a ejecutar el script).
                CONTINUE WHEN EXISTS (
                    SELECT 1 FROM cita_medica c
                     WHERE c.medico_id = v.medico_id AND c.inicio < v_fin AND c.fin > v_inicio
                       AND (c.estado_cita_id <> 4 OR c.inicio = v_inicio));

                k := k + 1;
                v_paciente := CASE WHEN v.especialidad_id = 2 AND ninos IS NOT NULL
                                   THEN ninos[1 + k % array_length(ninos, 1)]
                                   ELSE adultos[1 + (k * 7) % array_length(adultos, 1)] END;
                CONTINUE WHEN EXISTS (
                    SELECT 1 FROM cita_medica c
                     WHERE c.paciente_id = v_paciente AND c.estado_cita_id <> 4
                       AND c.inicio < v_fin AND c.fin > v_inicio);

                -- Estado según el momento del turno.
                IF v_fin <= ahora THEN
                    v_estado := CASE
                        WHEN d = 0 THEN (ARRAY[3, 1, 2, 3])[1 + k % 4]
                        WHEN d = -1 AND k % 3 = 0 THEN 1
                        ELSE (ARRAY[3, 3, 3, 3, 3, 3, 5, 5, 4, 3])[1 + k % 10] END;
                ELSIF v_inicio <= ahora THEN
                    v_estado := 2;
                ELSE
                    v_estado := (ARRAY[1, 1, 2, 1, 2, 4])[1 + k % 6];
                END IF;

                SELECT COALESCE((SELECT u.id FROM usuario u WHERE u.datos_personales_id = v_paciente AND u.perfil_id = 4 AND k % 2 = 0), 2)
                  INTO v_creador;
                v_creada := LEAST(v_inicio - make_interval(days => 2 + k % 6, hours => k % 9), ahora - INTERVAL '2 hours');
                v_motivo := motivos[1 + k % array_length(motivos, 1)];
                v_reprogramada := v_estado IN (1, 2) AND k % 5 = 0;
                v_previo := v_inicio - INTERVAL '2 days';

                n := n + 1;
                INSERT INTO cita_medica (codigo, paciente_id, medico_id, especialidad_id, estado_cita_id, inicio, fin,
                                         motivo, modalidad, importe_acordado, creada_por, fecha_creacion)
                VALUES ('CM-' || lpad(n::text, 4, '0'), v_paciente, v.medico_id, v.especialidad_id, v_estado, v_inicio, v_fin,
                        v_motivo, CASE WHEN k % 9 = 0 THEN 'virtual' ELSE 'presencial' END, v_importe, v_creador, v_creada)
                RETURNING id INTO v_cita;

                -- Historial: creación (con el horario original si luego se reprogramó).
                INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_nuevo_id, inicio_nuevo, fin_nuevo, fecha_hora)
                VALUES (v_cita, v_creador, 'creacion', 1,
                        CASE WHEN v_reprogramada THEN v_previo ELSE v_inicio END,
                        CASE WHEN v_reprogramada THEN v_previo + make_interval(mins => v.duracion_cita_min) ELSE v_fin END,
                        v_creada);
                INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                VALUES (v_creador, 'reservar', 'cita_medica', v_cita, v_creada,
                        CASE WHEN v_creador = 2 THEN '192.168.1.21' ELSE '181.115.20.4' END::inet);

                IF v_reprogramada THEN
                    INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id,
                                                inicio_anterior, fin_anterior, inicio_nuevo, fin_nuevo, observacion, fecha_hora)
                    VALUES (v_cita, v_creador, 'reprogramacion', 1, 1, v_previo, v_previo + make_interval(mins => v.duracion_cita_min),
                            v_inicio, v_fin, 'El paciente pidió otro día', v_creada + INTERVAL '1 hour');
                    INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                    VALUES (v_creador, 'reprogramar', 'cita_medica', v_cita, v_creada + INTERVAL '1 hour', NULL);
                END IF;

                -- Cambios de estado posteriores, con su evento y su registro en la bitácora.
                IF v_estado = 2 OR (v_estado = 3 AND k % 2 = 0) THEN
                    INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, fecha_hora)
                    VALUES (v_cita, 2, 'cambio_estado', 1, 2, v_inicio, v_fin, LEAST(v_inicio - INTERVAL '1 day', ahora - INTERVAL '1 hour'));
                    INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                    VALUES (2, 'confirmar', 'cita_medica', v_cita, LEAST(v_inicio - INTERVAL '1 day', ahora - INTERVAL '1 hour'), '192.168.1.21');
                END IF;

                IF v_estado = 3 THEN
                    INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, fecha_hora)
                    VALUES (v_cita, v_medico_usuario, 'cambio_estado', CASE WHEN k % 2 = 0 THEN 2 ELSE 1 END, 3, v_inicio, v_fin, v_fin);
                    INSERT INTO consulta (cita_id, fecha_hora, observaciones, diagnostico)
                    VALUES (v_cita, v_inicio + INTERVAL '3 minutes', 'Consulta por: ' || lower(v_motivo) || '.', diagnosticos[1 + k % array_length(diagnosticos, 1)]);
                    INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                    VALUES (v_medico_usuario, 'registrar', 'consulta', v_cita, v_fin, '192.168.1.35');
                    IF k % 3 <> 0 THEN
                        INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en, verificado_en)
                        VALUES (v_cita, 1, v_creador, gen_random_uuid(), 'VP-D' || lpad(v_cita::text, 6, '0'), v_importe, 'BOB', 'aprobado',
                                v_fin + INTERVAL '5 minutes', v_fin + INTERVAL '6 minutes');
                    END IF;
                ELSIF v_estado = 4 THEN
                    INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, observacion, fecha_hora)
                    VALUES (v_cita, v_creador, 'cambio_estado', 1, 4, v_inicio, v_fin,
                            motivos_cancelacion[1 + k % array_length(motivos_cancelacion, 1)],
                            LEAST(v_creada + INTERVAL '1 day', ahora - INTERVAL '30 minutes'));
                    INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                    VALUES (v_creador, 'cancelar', 'cita_medica', v_cita, LEAST(v_creada + INTERVAL '1 day', ahora - INTERVAL '30 minutes'), NULL);
                ELSIF v_estado = 5 THEN
                    INSERT INTO historial_cita (cita_id, usuario_id, tipo_evento, estado_anterior_id, estado_nuevo_id, inicio_nuevo, fin_nuevo, fecha_hora)
                    VALUES (v_cita, 2, 'cambio_estado', 1, 5, v_inicio, v_fin, LEAST(v_fin + INTERVAL '30 minutes', ahora));
                    INSERT INTO bitacora (usuario_id, accion, entidad, registro_id, fecha_hora, ip)
                    VALUES (2, 'registrar_inasistencia', 'cita_medica', v_cita, LEAST(v_fin + INTERVAL '30 minutes', ahora), '192.168.1.21');
                ELSIF v_estado IN (1, 2) AND v_inicio > ahora AND v_creador <> 2 AND k % 4 = 0 THEN
                    -- Algunas citas futuras pagadas en línea: al cancelarlas aparece el aviso de devolución (A1 de CU11).
                    INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en, verificado_en)
                    VALUES (v_cita, 1, v_creador, gen_random_uuid(), 'VP-D' || lpad(v_cita::text, 6, '0'), v_importe, 'BOB', 'aprobado',
                            v_creada + INTERVAL '10 minutes', v_creada + INTERVAL '11 minutes');
                END IF;
            END LOOP;
        END LOOP;
    END LOOP;
END $$;

COMMIT;
