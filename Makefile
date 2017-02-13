SHELL = /bin/sh
DOCKER = docker
DOCKER_COMPOSE = docker-compose
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_DEV_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_IMAGES = $(docker images -q -a)
DOCKER_IMAGES_API = $(docker images razorpay:api)
DOCKER_COMPOSE_PS = $(docker-compose ps -q)
DOCKER_PS_API_ALL = $(docker ps|grep "api_api_[0-9]\|api_api_db_[0-9]\|api_cache_[0-9]\|razorpay-es"|cut -d ' ' -f1)
DOCKER_PS_API = $(docker ps|grep "api_api_"|cut -d ' ' -f1)
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
COMPOSER = `which composer`

build:
	@echo "Shutting down and cleaning API images"
	# This step should be conditionally executed
	# if [ "${DOCKER_COMPOSE_PS}" != ""]; then
	-$(DOCKER_COMPOSE) down --remove-orphans
	# fi
	-$(DOCKER_RM) -f $(DOCKER_PS_API)
	-$(DOCKER_RMI) -f $(DOCKER_IMAGES_API)
	@echo "Installing necessary composer packages"
	$(COMPOSER) install
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) up -d --build
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
	-$(DOCKER_RM) $(DOCKER_PS_API_ALL)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) start
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE)

all: build
