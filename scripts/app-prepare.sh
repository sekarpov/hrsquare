#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
python3 scripts/init.py
# Application code must be readable by PHP-FPM and Caddy. Secrets stay 0600.
umask 022
mkdir -p app/vendor app/public/storage app/bootstrap/cache app/storage/framework/cache/data app/storage/framework/sessions app/storage/framework/views app/storage/logs
chmod 755 app/vendor app/bootstrap
mode=${1:-development}
if [ "$mode" = production ]; then
    docker compose run --rm --no-deps -e COMPOSER_HOME=/tmp/composer -v "$PWD/app/vendor:/app/vendor:rw" app composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
else
    docker compose run --rm --no-deps -e COMPOSER_HOME=/tmp/composer -v "$PWD/app/vendor:/app/vendor:rw" app composer install --prefer-dist --no-interaction
fi
# Composer runs as container root to handle cache dirs owned by PHP workers.
# Return vendor ownership to the operator so later deployment replacement works.
docker compose run --rm --no-deps -v "$PWD/app/vendor:/app/vendor:rw" app sh -ec 'chmod -R a+rX /app/vendor; chown -R "$1:$2" /app/vendor' sh "$(id -u)" "$(id -g)"
docker compose run --rm --no-deps --user "$(id -u):$(id -g)" node sh -ec 'npm ci && npm run build'
