SHELL = /bin/sh
DOCKER = docker
DOCKER_COMPOSE = docker-compose
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_PS = $(docker ps|grep "api_api_1\|api_db_live_1\|api_db_test_1\|api_cache_1"|cut -d ' ' -f1)
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh

build:
	echo "Shutting down and cleaning existing images"
	$(DOCKER_COMPOSE) down
	-$(DOCKER_RM) $(DOCKER_PS)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"

clean:
	$(DOCKER_COMPOSE) down
	-$(DOCKER_RM) $(DOCKER_PS)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) down

all: build
