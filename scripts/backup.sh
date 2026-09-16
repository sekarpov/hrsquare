#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
umask 077
mkdir -p backups
backup="backups/postgres-$(date -u +%Y%m%dT%H%M%SZ).dump"
trap 'rm -f "$backup.partial"' EXIT HUP INT TERM
docker compose exec -T postgres sh -ec 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$backup.partial"
test -s "$backup.partial"
mv "$backup.partial" "$backup"
find backups -type f -name 'postgres-*.dump' -mtime +14 -delete
printf '%s\n' "$backup"
