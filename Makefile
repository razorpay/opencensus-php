SHELL = /bin/sh
DOCKER = docker
DOCKER_COMPOSE = docker-compose
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_PS = $(docker ps|grep "api_api_[0-9]\|api_api_db_[0-9]\|api_cache_[0-9]\|razorpay-es"|cut -d ' ' -f1)
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
COMPOSER = `which composer`

build:
	echo "Shutting down and cleaning existing images"
	-$(DOCKER_COMPOSE) down --remove-orphans
	-$(DOCKER_RM) f $(DOCKER_PS)
	-$(DOCKER_RMI) f $(DOCKER_IMAGES)
	@echo "Installing necessary composer packages"
	$(COMPOSER) install
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Seeing elasticsearch indexes"
	@echo "===================="
	curl -X PUT "http://localhost:9200/api_live" -H 'Content-Type: application/json' -d @dockerconf/es_api_notes.json
	curl -X PUT "http://localhost:9200/api_test" -H 'Content-Type: application/json' -d @dockerconf/es_api_notes.json
	curl -X PUT "http://localhost:9200/audit_logs_live" -H 'Content-Type: application/json' -d @dockerconf/es_audit_logs.json
	curl -X PUT "http://localhost:9200/audit_logs_test" -H 'Content-Type: application/json' -d @dockerconf/es_audit_logs.json
	@echo "\n===================="
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"
	docker ps

clean:
	-$(DOCKER_COMPOSE) down --remove-orphans
	@echo $(DOCKER_PS)
	-$(DOCKER_RM) $(DOCKER_PS)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) start
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) 

all: build

