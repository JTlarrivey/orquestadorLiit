#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

# Ajustar Apache para escuchar en $PORT (Render/Railway/Fly/Cloud Run)
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \*:.*>!<VirtualHost *:${PORT}>!" /etc/apache2/sites-available/000-default.conf

# Opcional: mostrar el puerto elegido
echo "Starting Apache on port ${PORT} (DocumentRoot=${APACHE_DOCUMENT_ROOT})"

# Lanzar Apache en foreground (requerido por los PaaS)
exec apache2-foreground
