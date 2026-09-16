#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
# Refuse alternate names: RefreshDatabase must never touch the application DB.
docker compose exec -T postgres sh -ec '
    test "$POSTGRES_DB" != hrsquare_test
    if ! psql -U "$POSTGRES_USER" -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = '\''hrsquare_test'\''" | grep -q 1; then
        createdb -U "$POSTGRES_USER" hrsquare_test
    fi
'
docker compose exec -T --user www-data -e APP_ENV=testing -e DB_DATABASE=hrsquare_test -e SESSION_DRIVER=array -e CACHE_STORE=array app php vendor/bin/phpunit
