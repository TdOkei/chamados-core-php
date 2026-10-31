#!/bin/sh
# Garante que a pasta de uploads exista e seja gravavel pelo PHP-FPM (www-data).
set -e
UPLOADS="${UPLOADS_PATH:-/var/www/uploads}"
mkdir -p "$UPLOADS/servicos"
chown www-data:www-data "$UPLOADS" "$UPLOADS/servicos"
exec docker-php-entrypoint "$@"
