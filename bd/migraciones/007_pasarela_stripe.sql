-- =====================================================================
-- 007 · Pasarela Stripe (modo prueba) para CU20
-- Idempotente: se puede aplicar sobre una base que ya tiene 003.
-- =====================================================================

BEGIN;

INSERT INTO pasarela_pago (codigo, nombre, activo)
VALUES ('stripe', 'Stripe (modo prueba)', TRUE)
ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre, activo = TRUE;

COMMIT;
