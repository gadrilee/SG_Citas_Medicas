-- =====================================================================
-- 008 · Datos de demostración para P4 (CU16–CU20). NO cargar en una instalación real.
-- Requiere 004, 006 y 007. Las fechas son relativas al día en que se ejecuta y el script
-- puede repetirse: solo completa lo que todavía falta.
--   · CU16: historias clínicas completas (antecedentes, medicamentos, intolerancias).
--   · CU18/CU19: estudios pendientes, en proceso y publicados en las consultas recientes.
--   · CU20: un intento pendiente sin respuesta (A2), un rechazo con reintento (A1) y
--           citas canceladas con pago aprobado que esperan su devolución (A3).
-- =====================================================================

BEGIN;

-- ---------- CU16 · Historias clínicas ----------
UPDATE ficha_medica f
   SET tipo_sangre                = COALESCE(f.tipo_sangre, (ARRAY['O+', 'A+', 'B+', 'O-', 'A-', 'AB+'])[1 + f.paciente_id % 6]),
       intolerancias_medicamentos = COALESCE(f.intolerancias_medicamentos, (ARRAY[NULL, 'Ibuprofeno: molestias gástricas', NULL, 'Amoxicilina: erupción cutánea'])[1 + f.paciente_id % 4]),
       enfermedades_cronicas      = COALESCE(f.enfermedades_cronicas, (ARRAY['Hipertensión arterial', NULL, 'Diabetes tipo 2', NULL, 'Asma leve intermitente'])[1 + f.paciente_id % 5]),
       medicamentos_actuales      = COALESCE(f.medicamentos_actuales, (ARRAY['Losartán 50 mg cada 24 h', NULL, 'Metformina 850 mg con el almuerzo', NULL, 'Salbutamol inhalado si hay crisis'])[1 + f.paciente_id % 5]),
       antecedentes_personales    = COALESCE(f.antecedentes_personales, (ARRAY['Apendicectomía (2015)', 'Sin cirugías previas', 'Fractura de radio (2019)'])[1 + f.paciente_id % 3]),
       antecedentes_familiares    = (ARRAY['Padre con diabetes tipo 2', 'Madre hipertensa', 'Sin antecedentes relevantes', 'Abuela con cáncer de mama'])[1 + f.paciente_id % 4]
 WHERE f.antecedentes_familiares IS NULL;

-- ---------- CU18 y CU19 · Estudios de las consultas de las últimas 3 semanas ----------
-- Por médico, de cada 4 consultas sin estudios: una con estudio pendiente, otra en proceso y
-- otra publicado (la cuarta queda sin estudios).
WITH recientes AS (
    SELECT co.id AS consulta_id, co.fecha_hora, c.medico_id,
           row_number() OVER (PARTITION BY c.medico_id ORDER BY co.fecha_hora DESC) AS n
      FROM consulta co
      JOIN cita_medica c ON c.id = co.cita_id
     WHERE co.fecha_hora >= now() - INTERVAL '21 days'
       AND NOT EXISTS (SELECT 1 FROM estudio_laboratorio es WHERE es.consulta_id = co.id)
), elegidos AS (
    SELECT r.*, (ARRAY['Hemograma completo', 'Perfil lipídico', 'Glucosa en ayunas', 'Examen general de orina', 'Perfil tiroideo (TSH, T4)'])[1 + (r.n % 5)::int] AS nombre,
           (ARRAY['pendiente', 'en_proceso', 'disponible', NULL])[1 + ((r.n - 1) % 4)::int] AS estado,
           r.fecha_hora + INTERVAL '5 minutes' AS solicitado
      FROM recientes r
)
INSERT INTO estudio_laboratorio (consulta_id, nombre_estudio, indicaciones, solicitado_en, estado, resultado_texto, publicado_en, publicado_por)
SELECT e.consulta_id, e.nombre,
       CASE WHEN e.nombre IN ('Hemograma completo', 'Glucosa en ayunas', 'Perfil lipídico') THEN 'En ayunas de 8 a 12 horas' ELSE NULL END,
       e.solicitado, e.estado,
       CASE WHEN e.estado = 'disponible' THEN CASE e.nombre
            WHEN 'Hemograma completo'        THEN 'Hemoglobina 13,8 g/dL; leucocitos 7.200/mm³; plaquetas 260.000/mm³. Valores dentro de rango.'
            WHEN 'Perfil lipídico'           THEN 'Colesterol total 215 mg/dL (levemente elevado); HDL 48 mg/dL; LDL 138 mg/dL; triglicéridos 145 mg/dL.'
            WHEN 'Glucosa en ayunas'         THEN 'Glucosa 92 mg/dL. Valor normal.'
            WHEN 'Examen general de orina'   THEN 'Aspecto claro, densidad 1.020, sin leucocitos ni nitritos. Sin hallazgos patológicos.'
            ELSE 'TSH 2,1 µUI/mL; T4 libre 1,2 ng/dL. Función tiroidea normal.' END END,
       CASE WHEN e.estado = 'disponible' THEN GREATEST(e.solicitado, LEAST(e.solicitado + INTERVAL '2 days', now())) END,
       CASE WHEN e.estado = 'disponible' THEN (SELECT u.id FROM usuario u WHERE u.datos_personales_id = e.medico_id) END
  FROM elegidos e
 WHERE e.estado IS NOT NULL;

-- ---------- CU20 · Pagos ----------
DO $$
DECLARE
    v_cita record;
    v_maria bigint := (SELECT datos_personales_id FROM usuario WHERE nombre_usuario = 'maria.lopez');
BEGIN
    -- A3: citas canceladas recientes con un pago aprobado → aparecen en «Devoluciones por realizar».
    IF NOT EXISTS (SELECT 1 FROM pago pg JOIN cita_medica c ON c.id = pg.cita_id WHERE c.estado_cita_id = 4 AND pg.estado = 'aprobado') THEN
        FOR v_cita IN
            SELECT c.* FROM cita_medica c
             WHERE c.estado_cita_id = 4 AND c.importe_acordado > 0
               AND NOT EXISTS (SELECT 1 FROM pago pg WHERE pg.cita_id = c.id)
             ORDER BY c.inicio DESC LIMIT 2
        LOOP
            INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en, verificado_en)
            VALUES (v_cita.id, 1, v_cita.creada_por, gen_random_uuid(), 'VP-DV' || lpad(v_cita.id::text, 5, '0'),
                    v_cita.importe_acordado, v_cita.moneda, 'aprobado', v_cita.fecha_creacion + INTERVAL '10 minutes', v_cita.fecha_creacion + INTERVAL '11 minutes');
        END LOOP;
    END IF;

    -- A2: un intento iniciado en caja que la pasarela nunca confirmó (para «Verificar» o «Anular»).
    IF NOT EXISTS (SELECT 1 FROM pago WHERE estado = 'pendiente' AND creado_en > now() - INTERVAL '3 days') THEN
        SELECT c.* INTO v_cita FROM cita_medica c
         WHERE c.estado_cita_id IN (1, 2) AND c.inicio > now() AND c.importe_acordado > 0 AND c.paciente_id <> v_maria
           AND NOT EXISTS (SELECT 1 FROM pago pg WHERE pg.cita_id = c.id)
         ORDER BY c.inicio LIMIT 1;
        IF FOUND THEN
            INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en)
            VALUES (v_cita.id, 1, 2, gen_random_uuid(), 'VP-PE' || lpad(v_cita.id::text, 5, '0'), v_cita.importe_acordado, v_cita.moneda, 'pendiente', now() - INTERVAL '2 hours');
        END IF;
    END IF;

    -- A1: María intentó pagar su próxima cita y la tarjeta fue rechazada; puede reintentar.
    SELECT c.* INTO v_cita FROM cita_medica c
     WHERE c.paciente_id = v_maria AND c.estado_cita_id IN (1, 2) AND c.inicio > now() AND c.importe_acordado > 0
       AND NOT EXISTS (SELECT 1 FROM pago pg WHERE pg.cita_id = c.id)
     ORDER BY c.inicio LIMIT 1;
    IF FOUND AND NOT EXISTS (SELECT 1 FROM pago pg JOIN cita_medica c ON c.id = pg.cita_id WHERE c.paciente_id = v_maria AND pg.estado = 'rechazado' AND pg.creado_en > now() - INTERVAL '3 days') THEN
        INSERT INTO pago (cita_id, pasarela_id, iniciado_por, clave_idempotencia, referencia_externa, importe, moneda, estado, creado_en, verificado_en)
        VALUES (v_cita.id, 1, 8, gen_random_uuid(), 'VP-RE' || lpad(v_cita.id::text, 5, '0'), v_cita.importe_acordado, v_cita.moneda, 'rechazado', now() - INTERVAL '1 day', now() - INTERVAL '1 day' + INTERVAL '1 minute');
    END IF;
END $$;

COMMIT;
