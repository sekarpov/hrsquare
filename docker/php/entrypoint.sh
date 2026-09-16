#!/bin/sh
set -eu
if [ "${1:-}" = php-fpm ]; then
    mkdir -p /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache
    chown -R www-data:www-data /app/storage /app/bootstrap/cache
fi
exec docker-php-entrypoint "$@"
