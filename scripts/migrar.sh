#!/usr/bin/env bash
# Aplica el esquema y las migraciones a una base PostgreSQL (Neon, Render o local).
#   DATABASE_URL="postgres://usuario:clave@host/base?sslmode=require" ./scripts/migrar.sh
#   ./scripts/migrar.sh --con-demo      también carga 004_datos_demo.sql
# Pensado para una base vacía: los scripts crean tablas y no se pueden ejecutar dos veces.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f .env ] && [ -z "${DATABASE_URL:-}" ]; then
  DATABASE_URL="$(grep -E '^DATABASE_URL=' .env | cut -d= -f2-)"
fi
: "${DATABASE_URL:?Define DATABASE_URL o créala en .env}"

archivos=(
  bd/clinica_vida_salud.sql
  bd/migraciones/002_restricciones.sql
  bd/migraciones/003_catalogos.sql
)
if [ "${1:-}" = "--con-demo" ]; then
  archivos+=(bd/semillas/004_datos_demo.sql)
fi

for archivo in "${archivos[@]}"; do
  echo "→ $archivo"
  psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -q -f "$archivo"
done
echo "Base de datos lista."
