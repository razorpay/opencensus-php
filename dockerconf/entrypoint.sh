#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "$(date) Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
if [[ "${APP_CONTEXT}" == "dev" ]]; then
  echo "$(date) Configuring App"
  cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
  cp environment/.env.sample environment/.env.docker && \
  cp environment/env.sample.php environment/env.php

  # Change env to dev_docker
  sed -i 's/dev/dev_docker/g' environment/env.php

  # Common changes for the app, db and redis hosts, cache drivers
  sed -i 's/^APP_URL="https://api.razorpay.com"/#APP_URL="https://api.razorpay.com"/' environment/.env.docker
  sed -i 's/^APP_HOST="api.razorpay.com"/#APP_HOST="api.razorpay.com"/' environment/.env.docker

  # Now create dev_docker and testing_docker
  cp environment/.env.docker environment/.env.dev_docker && \
  cp environment/.env.docker environment/.env.testing_docker

  # Set DB name for testing
  sed -i 's/^DB_LIVE_DATABASE=api_live/DB_LIVE_DATABASE=api_testing_live/' environment/.env.testing_docker
  sed -i 's/^DB_TEST_DATABASE=api_test/DB_TEST_DATABASE=api_testing_test/' environment/.env.testing_docker
  sed -i 's/^DB_AUTH_DATABASE=auth/DB_AUTH_DATABASE=auth_test/' environment/.env.testing_docker
  sed -i 's/^SLAVE_DB_LIVE_DATABASE=api_live/SLAVE_DB_LIVE_DATABASE=api_testing_live/' environment/.env.testing_docker
  sed -i 's/^SLAVE_DB_TEST_DATABASE=api_test/SLAVE_DB_TEST_DATABASE=api_testing_test/' environment/.env.testing_docker

  # Remove temp file
  rm environment/.env.docker
  # Fix memory limit
  echo "$(date) Memory Limit"
  ## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
  ## Mac's sed idiosyncrasies :(
  echo 'memory_limit = 128M' | sed -E 's/memory_limit\s*=\s*\d*M/memory_limit = 3048M/g' /etc/php7/php.ini > /tmp/php.ini
  mv /tmp/php.ini /etc/php7/php.ini
else
  # copy apache2 config
  cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf

  # change log path
  ACCESS_LOG_PATH="CustomLog /var/log/apache2/api.razorpay.in.access.log custom_combined"
  sed -i "s|CustomLog|${ACCESS_LOG_PATH}|g" /etc/apache2/conf.d/api.conf

  # change domain reference
  if [[ "${APP_CONTEXT}" != "prod" ]]; then
    sed -i "s|api.razorpay.in|${APP_CONTEXT}-api.razorpay.com|g" /etc/apache2/conf.d/api.conf
  elif [[ "${APP_CONTEXT}" == "prod" ]]; then
    sed -i "s|api.razorpay.in|api.razorpay.com|g" /etc/apache2/conf.d/api.conf
  fi

  # use alohomora to generate vault and env.php
  # TODO: APP_CONTEXT would be an arbitrary string when deployed in k8s
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_CONTEXT --app $APP_NAME "environment/.env.vault.j2"
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_CONTEXT --app $APP_NAME "environment/env.php.j2"
fi

# DB Migrate
echo  "$(date) DB Migrate"
echo "$(date) Seeding live database"
cd /app/ && \
php artisan rzp:dbr --install --seed
echo "$(date) Seeding Test database"
APP_ENV=testing_docker php artisan rzp:dbr --install
echo "$(date) Seeding Auth Live database"
php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations
echo "$(date) Seeding Auth Test database"
APP_ENV=testing_docker php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations

echo "$(date) Starting Apache"
export PATH=$PATH:/app/:/app/vendor/bin/

# start httpd
echo "$(date) Apache"
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/httpd -D FOREGROUND
