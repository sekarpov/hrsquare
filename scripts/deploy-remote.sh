#!/bin/sh
set -eu
site_root=$1
action=$2
cd "$site_root"
test -f .env || { echo 'Run provisioning first: server .env is missing' >&2; exit 1; }
exec 9>.deploy.lock
flock -n 9 || { echo 'Another deployment is running' >&2; exit 1; }
umask 077
stage=$(mktemp -d "$site_root/.deploy-XXXXXXXX")
trap 'rm -rf "$stage"' EXIT HUP INT TERM
if [ "$action" = deploy ]; then
    tar -xpzf - -C "$stage"
    mkdir -p "$stage/app/public/storage"
    # A copy stays on the server, in a private temporary directory.
    cp .env "$stage/.env"
    (cd "$stage" && docker compose config --quiet && docker compose build app)
    # Snapshot only deployment files, never .env, data volumes or backups.
    tar -czf "$stage/previous.tar.gz" docker-compose.yml docker app scripts
    mv "$stage/previous.tar.gz" .deploy-previous.tar.gz
else
    test -s .deploy-previous.tar.gz || { echo 'No previous deployment available' >&2; exit 1; }
    tar -xpzf .deploy-previous.tar.gz -C "$stage"
    mkdir -p "$stage/app/public/storage"
    cp .env "$stage/.env"
    (cd "$stage" && docker compose config --quiet && docker compose build app)
fi
# Stop PHP and web while replacing bind-mounted source files.
# PostgreSQL and Redis keep running. A failed activation can be recovered with make rollback.
docker compose stop web app
for directory in app docker scripts; do
    rm -rf "$site_root/$directory"
    cp -R --preserve=mode "$stage/$directory" "$site_root/$directory"
done
cp --preserve=mode "$stage/docker-compose.yml" docker-compose.yml
mkdir -p app/public/storage
docker compose up -d --wait --wait-timeout 180
docker compose exec -T --user root app chown www-data:www-data /app/public/storage
docker compose exec -T --user www-data app sh -ec 'test -d /app/public/storage && test -w /app/public/storage'
docker compose exec -T web sh -ec 'wget -q -O /dev/null "$SITE_ADDRESS"'
printf '%s\n' 'Deployment successful'
