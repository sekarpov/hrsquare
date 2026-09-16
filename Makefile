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
	cd provisioning && ANSIBLE_LOCAL_TEMP=/tmp/hrsquare-ansible ansible-playbook -i hosts.yml.dist site.yml --syntax-check
