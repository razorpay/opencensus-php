#/usr/bin/env bash

# Setup:
# 1. Instal protoc
#       Refer https://github.com/google/protobuf/releases
# 2. Install protoc-gen-twirp_php plugin
#       Run `go get github.com/twirphp/twirp/protoc-gen-twirp_php`
#       Refer https://twirphp.readthedocs.io/en/latest/getting-started/installation.html
#   alternate approach for TwirPHP protoc plugin:
#       `curl -Ls https://git.io/twirphp | bash`
#       add protoc-gen-twirp_php file to path
#
# Generates php client code.
# Expects proto directory exists in parallel to this repository.
# Expects protoc and protoc-gen-twirp_php bin setup on system.
parentdir=$(dirname `pwd`)
[ -d generated/proto ] || mkdir generated/proto

protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/custom-domain-service/app/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/custom-domain-service/domain/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/custom-domain-service/propagation/v1/*

protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/common/mode/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/apikey/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/consumer/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/identifier/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/migrate/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/platform/bvs/validation/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/platform/bvs/probe/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/platform/bvs/validation/v2/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/platform/obs/verification/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/platform/bvs/consentdocumentmanager/v2/*
#
# this will generate swagger json files
protoc -I "$parentdir"/proto --openapiv2_out "$parentdir"/api/generated/proto \
  --openapiv2_opt grpc_api_configuration="$parentdir"/authz/grpc_api_configuration.yaml \
  "$parentdir"/proto/authz/admin/v1/*

protoc -I "$parentdir"/proto --openapiv2_out "$parentdir"/api/generated/proto \
  --openapiv2_opt grpc_api_configuration="$parentdir"/authz/grpc_api_configuration.yaml \
  "$parentdir"/proto/authz/enforcer/v1/*

# using those json we can generate AuthzClient
#https://github.com/swagger-api/swagger-codegen#getting-started

#java -jar modules/swagger-codegen-cli/target/swagger-codegen-cli.jar generate \
#   -i /Users/anand/Desktop/workspace/api/generated/proto/authz/admin/v1/admin_api.swagger.json \
#   -l php \
#   --invoker-package "AuthzAdmin\Client" \
#   -o /Users/anand/Desktop/workspace/api/generated/proto/authz/admin/v1/

#java -jar modules/swagger-codegen-cli/target/swagger-codegen-cli.jar generate \
#   -i /Users/anand/Desktop/workspace/api/generated/proto/authz/enforcer/v1/enforcer_api.swagger.json \
#   -l php \
#   -o /Users/anand/Desktop/workspace/api/generated/proto/authz/enforcer/v1/

# After generating client please run - composer dump-autoload -o
