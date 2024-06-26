#!/bin/bash
# Copyright 2018 OpenCensus Authors
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

set -e

pushd $(dirname ${BASH_SOURCE[0]})
source ../setup_test_repo.sh

composer create-project --prefer-dist symfony/skeleton symfony_test ^4.0
cp -r src tests phpunit.xml.dist symfony_test/

pushd symfony_test

composer require symfony/orm-pack

composer config repositories.opencensus git ${REPO}
composer require opencensus/opencensus:dev-${BRANCH}
composer require --dev phpunit/phpunit:^9.0 guzzlehttp/guzzle:~6.0

bin/console doctrine:migrations:migrate -n --allow-no-migration

echo "Running PHP server at ${TEST_HOST}:${TEST_PORT}"
php -S ${TEST_HOST}:${TEST_PORT} -t public &

vendor/bin/phpunit

# Clean up running PHP processes
function cleanup {
    echo "Killing PHP processes..."
    killall php
}
trap cleanup EXIT INT QUIT TERM

popd
popd
