# ---------- Etapa 1: compila los estilos con el binario standalone de Tailwind (sin Node) ----------
FROM debian:bookworm-slim AS estilos
ARG TAILWIND_VERSION=v3.4.17
RUN apt-get update \
 && apt-get install -y --no-install-recommends curl ca-certificates \
 && rm -rf /var/lib/apt/lists/* \
 && curl -fsSL -o /usr/local/bin/tailwindcss \
      "https://github.com/tailwindlabs/tailwindcss/releases/download/${TAILWIND_VERSION}/tailwindcss-linux-x64" \
 && chmod +x /usr/local/bin/tailwindcss
WORKDIR /src
COPY tailwind.config.js ./
COPY recursos ./recursos
COPY app ./app
COPY publico/js ./publico/js
RUN tailwindcss -c tailwind.config.js -i recursos/css/app.css -o publico/css/app.css --minify

# ---------- Etapa 2: PHP 8.3 + Apache con pdo_pgsql ----------
FROM php:8.3-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod headers \
 && a2dissite 000-default
COPY docker/apache-vida-salud.conf /etc/apache2/sites-available/vida-salud.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/vida-salud.ini
COPY docker/iniciar.sh /usr/local/bin/iniciar-vida-salud
RUN a2ensite vida-salud && chmod +x /usr/local/bin/iniciar-vida-salud

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=estilos --chown=www-data:www-data /src/publico/css/app.css publico/css/app.css

# Render asigna el puerto en $PORT (10000 por defecto).
ENV PORT=10000
EXPOSE 10000
CMD ["iniciar-vida-salud"]
