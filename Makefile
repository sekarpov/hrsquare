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
	docker compose up -d --build --wait --wait-timeout 180
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
	sh -n scripts/deploy-remote.sh scripts/backup.sh
	cd provisioning && ANSIBLE_LOCAL_TEMP=/tmp/hrsquare-ansible ansible-playbook -i hosts.yml.dist site.yml --syntax-check

.PHONY: deploy rollback
deploy:
	python3 scripts/deploy.py deploy
rollback:
	python3 scripts/deploy.py rollback
