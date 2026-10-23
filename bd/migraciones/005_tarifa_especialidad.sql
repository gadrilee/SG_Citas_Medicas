-- =====================================================================
-- 005 · Tarifa de consulta por especialidad
-- La cita guarda importe_acordado al reservar (CU09); este valor es el que se
-- copia en ese momento. Se administra en CU06. Un cambio de tarifa no altera
-- las citas ya reservadas.
-- =====================================================================

BEGIN;

ALTER TABLE especialidad
    ADD COLUMN importe_consulta NUMERIC(12,2) NOT NULL DEFAULT 0,
    ADD CONSTRAINT ck_especialidad_importe CHECK (importe_consulta >= 0);

UPDATE especialidad SET importe_consulta = CASE lower(nombre)
    WHEN 'medicina general' THEN 120
    WHEN 'pediatría'        THEN 150
    WHEN 'geriatría'        THEN 150
    WHEN 'ginecología'      THEN 180
    WHEN 'odontología'      THEN 140
    ELSE importe_consulta
END;

COMMIT;
