#Commands
SHELL = /bin/sh
DOCKER = docker
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_EXEC = docker exec
DOCKER_COMPOSE = docker-compose

#Variables populated from shell
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_IMAGES_API = $(shell docker images razorpay:api -q -a)
DOCKER_PS_API_IMG = $(shell docker ps|grep "razorpay:api"|head -n 1|cut -d ' ' -f1)
DOCKER_PS_API_ALL = $(shell docker ps|grep "razorpay:api"|cut -d ' ' -f1)
DOCKER_PS_ALL_API_ALL = $(shell docker ps|grep "razorpay:api\|api_db\|_cache\|elasticsearch"|cut -d ' ' -f1)

#Files used
DOCKER_DEV_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
DOCKER_ES_API_NOTES_JSON = dockerconf/es_api_notes.json
DOCKER_ES_AUDIT_LOGS_JSON = dockerconf/es_audit_logs.json
DOCKER_INIT_SCRIPT = dockerconf/docker-init.sh
#DOCKER_COMPOSE_PS = $(shell docker-compose ps -q)

#Misc
COMPOSER = `which composer`
PHPUNIT = vendor/bin/phpunit
PHPUNIT_ENV_FLAG = APP_ENV=testing_docker
AT=

init:
	@echo "Initializing and restarting docker with disabled flushing"
	$(SHELL) $(DOCKER_INIT_SCRIPT)
	@echo "Now you may execute 'make build' to build the necessary containers"

build: clean
	@echo "Installing necessary composer packages"
	$(COMPOSER) install
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Seeding elasticsearch indexes"
	@echo "===================="
	curl -X PUT "http://localhost:29200/api_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://localhost:29200/api_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://localhost:29200/audit_logs_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	curl -X PUT "http://localhost:29200/audit_logs_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	@echo "\n===================="
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"
	docker ps

clean:
	@echo "Remove orphan containers"
	-$(DOCKER_COMPOSE) down --remove-orphans
	@echo "Remove api containers if available"
	if [ "x$(DOCKER_PS_API_ALL)" != x ]; then $(DOCKER_RM) $(DOCKER_PS_API_ALL); fi
	@echo "Remove api images containers if available"
	if [ "x$(DOCKER_IMAGES_API)" != x ]; then $(DOCKER_RMI) $(DOCKER_IMAGES_API); fi

clean-all:
	@echo "Remove orphan containers"
	-$(DOCKER_COMPOSE) down --remove-orphans
	@echo "Remove all api containers if available"
	if [ "x$(DOCKER_PS_ALL_API_ALL)" != x ]; then $(DOCKER_RM) $(DOCKER_PS_ALL_API_ALL); fi
	@echo "Remove all api and services images containers if available"
	if [ "x$(DOCKER_IMAGES)" != x ]; then $(DOCKER_RMI) $(DOCKER_IMAGES); fi

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) unpause
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) pause

test:
	$(DOCKER_EXEC) --env $(PHPUNIT_ENV_FLAG) -it $(DOCKER_PS_API_IMG) $(PHPUNIT) $(AT)

all: build

