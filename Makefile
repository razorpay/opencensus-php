#Commands
SHELL = /bin/sh
DOCKER = docker
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_STOP = docker stop
DOCKER_EXEC = docker exec
DOCKER_COMPOSE = docker-compose

#Variables populated from shell
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_IMAGES_API = $(shell docker images razorpay:api -q -a)
DOCKER_PS_API_IMG = $(shell docker ps|grep "razorpay:api"|head -n 1|cut -d ' ' -f1)
DOCKER_PS_API_ALL = $(shell docker ps|grep "razorpay:api"|cut -d ' ' -f1)
DOCKER_PS_ALL_API_ALL = $(shell docker ps|grep "razorpay:api\|api_db\|_cache\|elasticsearch"|cut -d ' ' -f1)
DOCKER_PS_REDIS_CLUSTER_CONTAINER_ID =  $(shell docker ps|grep "api-redis-cluster"|cut -d ' ' -f1)

#Files used
DOCKER_DEV_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh
DOCKER_ES_API_NOTES_JSON = dockerconf/es_api_notes.json
DOCKER_ES_AUDIT_LOGS_JSON = dockerconf/es_audit_logs.json
DOCKER_ES_WORKFLOW_ACTIONS_JSON = dockerconf/es_workflow_actions.json
DOCKER_INIT_SCRIPT = dockerconf/docker-init.sh
#DOCKER_COMPOSE_PS = $(shell docker-compose ps -q)

#Misc
COMPOSER = `which composer`
PHPUNIT = vendor/bin/phpunit
PHPUNIT_ENV_FLAG = APP_ENV=testing_docker
AT=

# ERROR_MODULE repo info
ERROR_MODULE_GIT_URL := "https://github.com/razorpay/"
GIT_TOKEN := "$(cat /run/secrets/git_token)"
DRONE_ERROR_MODULE_GIT_URL := "https://$(GIT_TOKEN)@github.com/razorpay/error-mapping-module"
ifneq ($(GIT_TOKEN),)
ERROR_MODULE_GIT_URL = $(DRONE_ERROR_MODULE_GIT_URL)
endif

# Change this branch name for local testing
ERROR_MODULE_BRANCH := master
# Do not Change below code till endif
API_BRANCH := $(shell git for-each-ref --format='%(objectname) %(refname:short)' refs/heads | awk "/^$$(git rev-parse HEAD)/ {print \$$2}")
ifeq ($(API_BRANCH),master)
ERROR_MODULE_BRANCH = master
endif
ERROR_MODULE_ROOT := error_codes/

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
	curl -X PUT "http://0.0.0.0:29200/api_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://0.0.0.0:29200/api_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_API_NOTES_JSON)
	curl -X PUT "http://0.0.0.0:29200/audit_logs_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	curl -X PUT "http://0.0.0.0:29200/audit_logs_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_AUDIT_LOGS_JSON)
	curl -X PUT "http://0.0.0.0:29200/workflow_action_live" -H 'Content-Type: application/json' -d @$(DOCKER_ES_WORKFLOW_ACTIONS_JSON)
	curl -X PUT "http://0.0.0.0:29200/workflow_action_test" -H 'Content-Type: application/json' -d @$(DOCKER_ES_WORKFLOW_ACTIONS_JSON)
	@echo "\n===================="
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"
	docker ps

redis-cluster:
	if [ "x$(DOCKER_PS_REDIS_CLUSTER_CONTAINER_ID)" != x ]; then $(DOCKER_STOP) $(DOCKER_PS_REDIS_CLUSTER_CONTAINER_ID); $(DOCKER_RM) $(DOCKER_PS_REDIS_CLUSTER_CONTAINER_ID); fi
	$(DOCKER) run -e IP=0.0.0.0 -p 7000-7050:7000-7050 -p 5000-5010:5000-5010 -d grokzen/redis-cluster:5.0.9

clean:
	$(DOCKER_COMPOSE) -f $(DOCKER_DEV_COMPOSE_FILE) down --remove-orphans
	$(DOCKER) system prune -f

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

error-module-clean:
	@echo " + Removing fetched error-mapping-files\n"
	@rm -rf $(ERROR_MODULE_ROOT)


error-module-fetch: ## Fetch ERROR_MODULE files from remote repo
	@echo "\n + Fetching ERROR_MODULE files from branch: $(ERROR_MODULE_BRANCH) \n"
	@mkdir $(ERROR_MODULE_ROOT) && \
	cd $(ERROR_MODULE_ROOT) && \
	git init --quiet && \
	git config core.sparseCheckout true && \
	cp $(CURDIR)/error_modules .git/info/sparse-checkout && \
	git remote add origin $(ERROR_MODULE_GIT_URL)  && \
	git fetch origin $(ERROR_MODULE_BRANCH) --quiet && \
	git checkout origin/$(ERROR_MODULE_BRANCH) --quiet
