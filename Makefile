HOST ?= 216.57.108.236
PORT ?= 22
DEPLOY_USER ?= deploy
DEPLOY_PATH ?= /opt/hrsquare
SSH_KEY ?=
export HOST PORT DEPLOY_USER DEPLOY_PATH SSH_KEY

.PHONY: init config up down logs ps tools backup check
init:
	python3 scripts/init.py
config:
	docker compose config --quiet
up:
	docker compose build app
	./scripts/app-prepare.sh
	docker compose up -d --wait --wait-timeout 180 postgres redis app
	docker compose exec -T app php artisan migrate --force
	docker compose up -d --wait --wait-timeout 180

down:
	docker compose down
logs:
	docker compose logs --tail=100 -f
ps:
	docker compose ps
tools:
	docker compose --profile tools up -d
backup:
	./scripts/backup.sh
check:
	docker compose config --quiet
	python3 -c "from pathlib import Path; [compile(Path(p).read_text(), p, 'exec') for p in ['scripts/deploy.py', 'scripts/init.py']]"
	sh -n scripts/deploy-remote.sh scripts/backup.sh scripts/app-prepare.sh scripts/test.sh docker/php/entrypoint.sh
	python3 -m unittest discover -s tests
	cd provisioning && ANSIBLE_LOCAL_TEMP=/tmp/hrsquare-ansible ansible-playbook -i hosts.yml.dist site.yml --syntax-check

.PHONY: deploy rollback
deploy:
	python3 scripts/deploy.py deploy
rollback:
	python3 scripts/deploy.py rollback

.PHONY: app-init frontend migrate seed test
app-init: up
frontend:
	docker compose run --rm --no-deps --user "$$(id -u):$$(id -g)" node sh -ec 'npm ci && npm run build'
migrate:
	docker compose exec -T app php artisan migrate --force
seed:
	docker compose exec -T app php artisan db:seed --class=DemoSeeder
# Tests run in a separate database on the existing PostgreSQL service.
test:
	./scripts/test.sh
