export ANSIBLE_LOCAL_TEMP ?= /tmp/hrsquare-ansible
.PHONY: site check
site:
	ansible-playbook -i hosts.yml site.yml
check:
	ansible-playbook -i hosts.yml.dist site.yml --syntax-check
