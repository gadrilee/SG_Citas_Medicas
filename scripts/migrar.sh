#!/usr/bin/env bash
# Aplica el esquema y las migraciones a una base PostgreSQL (Neon, Render o local).
#   DATABASE_URL="postgres://usuario:clave@host/base?sslmode=require" ./scripts/migrar.sh
#   ./scripts/migrar.sh --con-demo      también carga los datos demo (004, 006 y 008)
#   ./scripts/migrar.sh --solo-demo-p3  solo 006 (citas alrededor de hoy) sobre una base ya cargada
#   ./scripts/migrar.sh --solo-p4       solo 007 (pasarela Stripe) y 008 (demo de P4) sobre una base ya cargada
# Pensado para una base vacía: los scripts 001–005 crean tablas y no se pueden ejecutar dos veces.
# 006, 007 y 008 sí pueden repetirse: solo agregan lo que falta.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f .env ] && [ -z "${DATABASE_URL:-}" ]; then
  DATABASE_URL="$(grep -E '^DATABASE_URL=' .env | cut -d= -f2-)"
fi
: "${DATABASE_URL:?Define DATABASE_URL o créala en .env}"

case "${1:-}" in
  --solo-demo-p3)
    archivos=(bd/semillas/006_datos_demo_p3.sql)
    ;;
  --solo-p4)
    archivos=(bd/migraciones/007_pasarela_stripe.sql bd/semillas/008_datos_demo_p4.sql)
    ;;
  *)
    archivos=(
      bd/clinica_vida_salud.sql
      bd/migraciones/002_restricciones.sql
      bd/migraciones/003_catalogos.sql
    )
    if [ "${1:-}" = "--con-demo" ]; then
      archivos+=(bd/semillas/004_datos_demo.sql)
    fi
    archivos+=(bd/migraciones/005_tarifa_especialidad.sql)
    if [ "${1:-}" = "--con-demo" ]; then
      archivos+=(bd/semillas/006_datos_demo_p3.sql)
    fi
    archivos+=(bd/migraciones/007_pasarela_stripe.sql)
    if [ "${1:-}" = "--con-demo" ]; then
      archivos+=(bd/semillas/008_datos_demo_p4.sql)
    fi
    ;;
esac

for archivo in "${archivos[@]}"; do
  echo "→ $archivo"
  psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -q -f "$archivo"
done
echo "Base de datos lista."
