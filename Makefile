#Commands
SHELL = /bin/sh
DOCKER = docker
DOCKER_RMI = docker rmi -f
DOCKER_RM = docker rm -f
DOCKER_EXEC = docker exec
DOCKER_COMPOSE = docker-compose

#Variables populated from shell
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_IMAGES_API = $(shell docker images razorpay:dashboard -q -a)
DOCKER_PS_API_IMG = $(shell docker ps|grep "razorpay:dashboard"|head -n 1|cut -d ' ' -f1)
DOCKER_PS_API_ALL = $(shell docker ps|grep "razorpay:dashboard"|cut -d ' ' -f1)
DOCKER_PS_ALL_API_ALL = $(shell docker ps|grep "razorpay:dashboard\|dashboard_db\|dashboard_cache\"|cut -d ' ' -f1)

#Files used
DOCKER_DEV_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
DOCKER_SELENIUM_DOWNLOADER = dockerconf/selenium-downloader.sh

#Misc
COMPOSER = `which composer`
PHPUNIT = vendor/bin/phpunit
PHPUNIT_ENV_FLAG = APP_ENV=testing_docker
AT=

build: clean
	$(SHELL) $(DOCKER_SELENIUM_DOWNLOADER)
	@echo "Installing necessary composer packages"
	$(COMPOSER) install
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"
	docker ps

clean:
	@echo "Remove orphan containers"
	-$(DOCKER_COMPOSE) down 
	@echo "Remove dashboard containers if available"
	if [ "x$(DOCKER_PS_API_ALL)" != x ]; then $(DOCKER_RM) $(DOCKER_PS_API_ALL); fi
	@echo "Remove dashboard images containers if available"
	if [ "x$(DOCKER_IMAGES_API)" != x ]; then $(DOCKER_RMI) $(DOCKER_IMAGES_API); fi

clean-all:
	@echo "Remove orphan containers"
	-$(DOCKER_COMPOSE) down --remove-orphans
	@echo "Remove all dashboard containers if available"
	if [ "x$(DOCKER_PS_ALL_API_ALL)" != x ]; then $(DOCKER_RM) $(DOCKER_PS_ALL_API_ALL); fi
	@echo "Remove all dashboard and services images containers if available"
	if [ "x$(DOCKER_IMAGES)" != x ]; then $(DOCKER_RMI) $(DOCKER_IMAGES); fi

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) unpause
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) pause

test:
	$(DOCKER_EXEC) --env $(PHPUNIT_ENV_FLAG) -it $(DOCKER_PS_API_IMG) $(PHPUNIT) $(AT)

all: build

