#!/usr/bin/env bash
# Compila los estilos sin Docker (XAMPP u otro servidor local).
#   ./scripts/css.sh           compila una vez (minificado)
#   ./scripts/css.sh --watch   recompila al guardar cambios
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION="v3.4.17"
case "$(uname -s)-$(uname -m)" in
  Linux-x86_64)  binario="tailwindcss-linux-x64" ;;
  Linux-aarch64) binario="tailwindcss-linux-arm64" ;;
  Darwin-arm64)  binario="tailwindcss-macos-arm64" ;;
  Darwin-x86_64) binario="tailwindcss-macos-x64" ;;
  *) echo "Sistema no soportado; en Windows descarga tailwindcss-windows-x64.exe" >&2; exit 1 ;;
esac

mkdir -p herramientas
if [ ! -x herramientas/tailwindcss ]; then
  curl -fsSL -o herramientas/tailwindcss "https://github.com/tailwindlabs/tailwindcss/releases/download/${VERSION}/${binario}"
  chmod +x herramientas/tailwindcss
fi

if [ "${1:-}" = "--watch" ]; then
  herramientas/tailwindcss -c tailwind.config.js -i recursos/css/app.css -o publico/css/app.css --watch
else
  herramientas/tailwindcss -c tailwind.config.js -i recursos/css/app.css -o publico/css/app.css --minify
fi
