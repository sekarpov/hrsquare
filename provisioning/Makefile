export ANSIBLE_LOCAL_TEMP ?= /tmp/hrsquare-ansible
.PHONY: site check
site:
	ansible-playbook -i hosts.yml site.yml
check:
	ansible-playbook -i hosts.yml.dist site.yml --syntax-check
	ansible-playbook -i hosts.yml.dist authorize-deploy.yml --syntax-check

.PHONY: authorize-deploy
authorize-deploy:
	ansible-playbook -i hosts.yml authorize-deploy.yml
