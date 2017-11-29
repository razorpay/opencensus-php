#!/bin/sh
set -euo pipefail

db_wait(){
  echo "Waiting for DB to intialize"
  sleep 30
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
  echo "$(date) db names change done"

  # Remove temp file
  rm environment/.env.docker
  # Fix memory limit
  echo "$(date) Memory Limit"
  ## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
  ## Mac's sed idiosyncrasies :(
  echo 'memory_limit = 128M' | sed -E 's~memory_limit\s*=\s*\d*M~memory_limit = 3048M~g' /etc/php7/php.ini > /tmp/php.ini
  echo "$(date) Memory Limit done"
  mv /tmp/php.ini /etc/php7/php.ini
}

configure_cloud(){
  ALOHOMORA_BIN=$(which alohomora)
  echo "casting alohomora - vault"
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "environment/.env.vault.j2"
  echo "casting alohomora - env.php"
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "environment/env.php.j2"
  echo "casting alohomora - apache"
  sed -i "s|APACHE_HOST|$HOSTNAME|g" dockerconf/api.apache.conf.j2
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "dockerconf/api.apache.conf.j2"

  echo "copying nginx config"
  cp dockerconf/api.apache.conf /etc/apache2/conf.d/api.conf

  ## Enable newrelic only for prod
  if [[ "${APP_MODE}" == "prod" ]] || [[ "${APP_MODE}" == "perf" ]]; then
    $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "dockerconf/newrelic.ini.j2"
    cp dockerconf/newrelic.ini /etc/php7/conf.d/newrelic.ini
  fi
}

configure_app(){
  if [[ "${APP_MODE}" == "dev" ]]; then
    configure_dev
  else
    configure_cloud
  fi
}

configure_db_dev(){
  echo "$(date) Seeding live database"
  cd /app/ && \
  php artisan rzp:dbr --install --seed
  echo "$(date) Seeding Test database"
  APP_ENV=testing_docker php artisan rzp:dbr --install
  echo "$(date) Seeding Auth Live database"
  php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations
  echo "$(date) Seeding Auth Test database"
  APP_ENV=testing_docker php artisan migrate --database auth --path vendor/razorpay/oauth/database/migrations
}

configure_db_cloud(){
  # php artisan migrate --force && php artisan migrate --database=test --force
  # # Restart all queue worker processes
  # echo "Queue Restart"
  # php artisan queue:restart

  # Clear and Re-cache Routes
  echo "Route Cache"
  php artisan route:cache
}

configure_db(){
  echo  "$(date) DB Migrate"
  if [[ "$APP_MODE" == "dev" ]]; then
    configure_db_dev
  else
    configure_db_cloud
  fi
}

update_commit(){
  if [[ -n "${GIT_COMMIT_HASH-}" ]]; then
    echo "GIT_COMMIT_HASH=${GIT_COMMIT_HASH}" >> /app/.env.vault
    echo "${GIT_COMMIT_HASH}" > /app/public/commit.txt
  fi
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
  configure_app
  configure_db
  update_commit
}

### Check that atleast either webapp or supervisor is specified
if [ "$#" -eq 0 ]; then
    echo "Specify app type: < web | supervisor >"
    exit -1
fi

## Do the basic initialization and get the app type

function main {
  initialize
  app_type=$1

  ## Now, based on the app type, call the specific functions
  if [[ "${app_type}" == "web" ]]; then
    echo "Starting web app"
    start_apache
  elif [[ "${app_type}" == "sqs" ]]; then
    sleep_time=$2
    #['sqs', '10']
    if [ "$#" -ne 2 ]; then
        echo "Need to specify following args: "
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      php artisan queue:work ${app_type} --sleep=${sleep_time}
    fi
  elif [[ "${app_type}" == "sqs_multi_default" ]]; then
    queue_name=$2
    sleep_time=$3
    if [ "$#" -ne 3 ]; then
        echo "Need to specify following args: "
        echo "queue: <sqs-name>"
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      php artisan queue:work ${app_type} --queue=${APP_MODE}-${queue_name} --sleep=${sleep_time}
    fi
    else
      echo "Specify sqs-listener-type: <sqs | sqs_multi_default>"
    fi
  else
    echo "specify app-type: <web | sqs | sqs_multi_default>"
  fi

}

main $@
