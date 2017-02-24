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
DOCKER_PS_API = $(shell docker ps|grep "api_api_"|cut -d ' ' -f1)
DOCKER_PS_API_IMG = $(shell docker ps|grep "api_api_1"|cut -d ' ' -f1)
DOCKER_PS_API_ALL = $(shell docker ps|grep "api_api_[0-9]"|cut -d ' ' -f1)
DOCKER_PS_ALL_API_ALL = $(shell docker ps|grep "api_api_[0-9]\|api_api_db_[0-9]\|api_cache_[0-9]\|razorpay-es"|cut -d ' ' -f1)

#Files used
DOCKER_DEV_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
DOCKER_ES_API_NOTES_JSON = dockerconf/es_api_notes.json
DOCKER_ES_AUDIT_LOGS_JSON = dockerconf/es_audit_logs.json
#DOCKER_COMPOSE_PS = $(shell docker-compose ps -q)

#Misc
COMPOSER = `which composer`
PHPUNIT = vendor/bin/phpunit
PHPUNIT_ENV_FLAG = APP_ENV=testing_docker
PHPUNIT_ARGS =

build: clean
	@echo "Installing necessary composer packages"
	$(COMPOSER) install
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Seeding elasticsearch indexes"
	@echo "===================="
	curl -X PUT "http://localhost:9200/api_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://localhost:9200/api_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://localhost:9200/audit_logs_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	curl -X PUT "http://localhost:9200/audit_logs_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	@echo "\n===================="
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"
	docker ps

clean:
	-$(DOCKER_COMPOSE) down --remove-orphans
	-$(DOCKER_RM) $(DOCKER_PS_API_ALL)
	-$(DOCKER_RMI) $(DOCKER_IMAGES_API)

clean-all:
	-$(DOCKER_COMPOSE) down --remove-orphans
	-$(DOCKER_RM) $(DOCKER_PS_ALL_API_ALL)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) unpause
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) pause

test:
	$(DOCKER_EXEC) --env $(PHPUNIT_ENV_FLAG) -it $(DOCKER_PS_API_IMG) $(PHPUNIT) $(PHPUNIT_ARGS)

all: build
