#!/bin/bash


set -euo pipefail

if [[ -n "${GIT_COMMIT_HASH}" ]]; then
    echo "${GIT_COMMIT_HASH}" > /app/public/commit.txt
fi



# Install dependencies
npm install
if [[ "${APP_MODE}" == "dev" ]]; then
	gulp dev:webpack
else
	gulp
fi


cd /app/

ALOHOMORA_BIN=$(which alohomora)
$ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "dockerconf/dashboard.conf.j2"
sed -i "s|NGINX_HOST|$HOSTNAME|g" dockerconf/dashboard.conf
cp dockerconf/dashboard.conf /etc/nginx/conf.d/dashboard.conf
$ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/env.php.j2"

if [[ "${APP_MODE}" == "dev" ]]
then
  cp environment/.env.docker environment/.env.dev
else
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2"
fi

# Copy config
#cp environment/.env.example environment/.env.dev_docker && \
#cp environment/.env.example environment/.env.testing_docker && \
#cp environment/env.sample.php environment/env.php && \
#sed -i "s/dev/dev_docker/g" environment/env.php

# php.ini changes
sed -i 's/display_errors = Off/display_errors = On/' /etc/php7/php.ini
sed -i 's/display_startup_errors = Off/display_startup_errors = On/' /etc/php7/php.ini


#echo "$date Memory Limit"
## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
## Mac's sed idiosyncrasies :(
#echo 'memory_limit = 128M' | sed -E 's/memory_limit\s*=\s*\d*M/memory_limit = 3048M/g' /etc/php7/php.ini > /tmp/php.ini
#mv /tmp/php.ini /etc/php7/php.ini

# Fix permissions
echo  "$date Fix permissions"
chmod 777 -R storage
echo "$date Configuring App"

echo "$(date) DB Migrate"
echo "$(date) Seeding live db"
cd /app && \
php artisan migrate --seed

echo "$date Starting Nginx"
export PATH=$PATH:/app/

echo "$date Starting Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'
