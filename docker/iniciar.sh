#!/bin/sh
# Arranque del contenedor: Apache escucha en el puerto que entrega Render ($PORT).
set -e
PORT="${PORT:-10000}"
export PORT
echo "Listen ${PORT}" > /etc/apache2/ports.conf
# El nombre evita el aviso "Could not reliably determine the server's fully qualified domain name".
echo "ServerName localhost" > /etc/apache2/conf-enabled/nombre-servidor.conf
exec apache2-foreground
