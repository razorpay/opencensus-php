#!/bin/sh
set -euo pipefail

db_wait(){
  echo "Waiting for DB to intialize"
  until mysqladmin ping -h "$DB_LIVE_HOST" -u "$DB_LIVE_USERNAME" -p"$DB_LIVE_PASSWORD" --silent; do
        echo "Mysql DB is unavailable"
        sleep 1
    done
}

fix_permissions(){
  echo  "$(date) Fix permissions"
  cd /app/ && chmod 777 -R storage
}

configure_dev(){
  echo "$(date) Configuring App"
  cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
  cp environment/.env.sample environment/.env.docker && \
  cp environment/env.sample.php environment/env.php

  echo "$(date) env name change"
  # Change env to dev_docker
  sed -i 's~dev~dev_docker~g' environment/env.php
  echo "$(date) env name change done"

  echo "$(date) url and host change"
  # Common changes for the app, db and redis hosts, cache drivers
  sed -i 's~^APP_URL="https://api.razorpay.com"~#APP_URL="https://api.razorpay.com"~' environment/.env.docker
  sed -i 's~^APP_HOST="api.razorpay.com"~#APP_HOST="api.razorpay.com"~' environment/.env.docker
  echo "$(date) url and host change done"

  # Now create dev_docker and testing_docker
  cp environment/.env.docker environment/.env.dev_docker && \
  cp environment/.env.docker environment/.env.testing_docker

  # Set DB name for testing
  echo "$(date) db names change"
  sed -i 's~^DB_LIVE_DATABASE=api_live~DB_LIVE_DATABASE=api_testing_live~' environment/.env.testing_docker
  sed -i 's~^DB_TEST_DATABASE=api_test~DB_TEST_DATABASE=api_testing_test~' environment/.env.testing_docker
  sed -i 's~^DB_AUTH_DATABASE=auth~DB_AUTH_DATABASE=auth_test~' environment/.env.testing_docker
  sed -i 's~^SLAVE_DB_LIVE_DATABASE=api_live~SLAVE_DB_LIVE_DATABASE=api_testing_live~' environment/.env.testing_docker
  sed -i 's~^SLAVE_DB_TEST_DATABASE=api_test~SLAVE_DB_TEST_DATABASE=api_testing_test~' environment/.env.testing_docker
  sed -i 's~^DB_LIVE_VIEW_DATABASE=api_live~DB_LIVE_VIEW_DATABASE=api_testing_live~' environment/.env.testing_docker
  sed -i 's~^DB_TEST_VIEW_DATABASE=api_test~DB_TEST_VIEW_DATABASE=api_testing_test~' environment/.env.testing_docker

  echo "$(date) db names change done"

  # Remove temp file
  rm environment/.env.docker
  # Fix memory limit
  echo "$(date) Memory Limit"
  ## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
  ## Mac's sed idiosyncrasies :(
  echo 'memory_limit = 128M' | sed -E 's~memory_limit\s*=\s*\d*M~memory_limit = 3048M~g' /etc/php81/php.ini > /tmp/php.ini
  echo "$(date) Memory Limit done"
  mv /tmp/php.ini /etc/php81/php.ini

  # Throw exception on assertion failures
  touch /etc/php81/conf.d/assertion.ini
  echo "zend.assertions=1" >> /etc/php81/conf.d/assertion.ini
  echo "assert.exception=On" >> /etc/php81/conf.d/assertion.ini
}

configure_db_dev(){
  echo "$(date) Seeding live database"
  cd /app/ && \
  php artisan rzp:dbr --install --seed
  php artisan payments_upi:dbr
  echo "$(date) Seeding Test database"
  APP_ENV=testing_docker php artisan rzp:dbr --install
  echo "$(date) Seeding Auth Live database"
  php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations
  echo "$(date) Seeding Auth Test database"
  APP_ENV=testing_docker php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations
}

start_apache(){
  echo "$(date) Starting Apache"
  export PATH=$PATH:/app/:/app/vendor/bin/
  # start httpd
  echo "$(date) Apache"
  mkdir /tmp/run
  chown 0775 /tmp/run/
  /usr/sbin/httpd -D FOREGROUND
}

initialize(){
  db_wait
  fix_permissions
  configure_dev
  configure_db_dev
}

### Check that atleast either webapp or supervisor is specified

## Do the basic initialization and get the app type

function main {
  initialize
  echo "Starting web app"
  start_apache
}

main
