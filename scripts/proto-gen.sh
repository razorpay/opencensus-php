#/usr/bin/env bash

# Setup:
# 1. Instal protoc
#       Refer https://github.com/google/protobuf/releases
# 2. Install protoc-gen-twirp_php plugin
#       Run `go get github.com/twirphp/twirp/protoc-gen-twirp_php`
#       Refer https://twirphp.readthedocs.io/en/latest/getting-started/installation.html

# Generates php client code.
# Expects proto directory exists in parallel to this repository.
# Expects protoc and protoc-gen-twirp_php bin setup on system.
parentdir=$(dirname `pwd`)
[ -d generated/proto ] || mkdir generated/proto

protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/common/mode/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/apikey/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/common/external_entity/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/identifier/v1/*
protoc -I "$parentdir"/proto --twirp_php_out=generated/proto --php_out=generated/proto "$parentdir"/proto/credcase/migrate/v1/*
