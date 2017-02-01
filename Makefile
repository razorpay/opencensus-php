SHELL = /bin/sh
DOCKER = docker
DOCKER_COMPOSE = docker-compose
DOCKER_RMI = docker rmi
DOCKER_RM = docker rm
DOCKER_COMPOSE_FILE = docker-compose.dev.yml
DOCKER_IMAGES = $(shell docker images -q -a)
DOCKER_PS = $(docker ps|grep "api_api_1\|api_db_live_1\|api_db_test_1\|api_cache_1\|razorpay-es"|cut -d ' ' -f1)
DOCKER_STATUS_CHECKER = dockerconf/docker-status-check.sh

build:
	echo "Shutting down and cleaning existing images"
	$(DOCKER_COMPOSE) down --remove-orphans
	-$(DOCKER_RM) f $(DOCKER_PS)
	-$(DOCKER_RMI) f $(DOCKER_IMAGES)
	@echo "Building docker containers"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)
	@echo "Seeing elastic search indexes"
	@echo "===================="
	curl -XPOST "http://localhost:9200/api_live" -d' {"settings": {"number_of_shards": 5, "number_of_replicas": 1}, "mappings": { "payment": { "_all": { "enabled": false }, "properties": { "merchant_id": { "type": "string", "index": "not_analyzed" }, "notes": { "type": "object" }, "created": { "type": "date", "format": "yyyy-MM-dd HH:mm:ss||epoch_millis" } } }, "refund": { "_all": { "enabled": false }, "properties": { "merchant_id": { "type": "string", "index": "not_analyzed" }, "notes": { "type": "object" }, "created": { "type": "date", "format": "yyyy-MM-dd HH:mm:ss||epoch_millis" } } } } }'
	curl -XPOST "http://localhost:9200/api_test" -d' {"settings": {"number_of_shards": 5, "number_of_replicas": 1}, "mappings": { "payment": { "_all": { "enabled": false }, "properties": { "merchant_id": { "type": "string", "index": "not_analyzed" }, "notes": { "type": "object" }, "created": { "type": "date", "format": "yyyy-MM-dd HH:mm:ss||epoch_millis" } } }, "refund": { "_all": { "enabled": false }, "properties": { "merchant_id": { "type": "string", "index": "not_analyzed" }, "notes": { "type": "object" }, "created": { "type": "date", "format": "yyyy-MM-dd HH:mm:ss||epoch_millis" } } } } }'
	curl -H 'Content-Type: application/json' -X PUT "http://localhost:9200/audit_logs_live" -d'{"settings":{},"mappings":{"audit_log":{"_all":{"enabled":false},"properties":{"admin":{"properties":{"id":{"type":"text"},"username":{"type":"text"},"email":{"type":"text"},"name":{"type":"text"},"org_id":{"type":"text"},"employee_code":{"type":"text"},"branch_code":{"type":"text"},"department_code":{"type":"text"},"supervisor_code":{"type":"text"},"location_code":{"type":"text"},"roles":{"type":"text"},"groups":{"type":"text"}}},"category":{"type":"text"},"label":{"type":"text"},"action":{"type":"text"},"description":{"type":"text"},"entity":{"type":"object","enabled":false},"user_agent":{"type":"text"},"ip_address":{"type":"text"},"created_at":{"type":"long"},"extra":{"properties":{"org_id":{"type":"text"}}},"internal":{"properties":{"event":{"type":"text"}}}}}}}'
	curl -H 'Content-Type: application/json' -X PUT "http://localhost:9200/audit_logs_test" -d'{"settings":{},"mappings":{"audit_log":{"_all":{"enabled":false},"properties":{"admin":{"properties":{"id":{"type":"text"},"username":{"type":"text"},"email":{"type":"text"},"name":{"type":"text"},"org_id":{"type":"text"},"employee_code":{"type":"text"},"branch_code":{"type":"text"},"department_code":{"type":"text"},"supervisor_code":{"type":"text"},"location_code":{"type":"text"},"roles":{"type":"text"},"groups":{"type":"text"}}},"category":{"type":"text"},"label":{"type":"text"},"action":{"type":"text"},"description":{"type":"text"},"entity":{"type":"object","enabled":false},"user_agent":{"type":"text"},"ip_address":{"type":"text"},"created_at":{"type":"long"},"extra":{"properties":{"org_id":{"type":"text"}}},"internal":{"properties":{"event":{"type":"text"}}}}}}}'
	@echo "\n===================="
	@echo "Container build Setup Complete. You may now execute 'docker ps' to see if things are up"

clean:
	$(DOCKER_COMPOSE) down --remove-orphans
	-$(DOCKER_RM) $(DOCKER_PS)
	-$(DOCKER_RMI) $(DOCKER_IMAGES)

up:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d --build
	$(SHELL) $(DOCKER_STATUS_CHECKER)

down:
	$(DOCKER_COMPOSE) down

all: build

