#!/bin/bash

# Fix permissions
echo  "$date Fix permissions"
cd /app/ && chmod 777 -R storage
echo "$date Configuring App"

yarn install
gulp
# Copy config
cp dockerconf/dashboard.conf /etc/nginx/conf.d/dashboard.conf && \
cp environment/.env.example environment/.env.dev_docker && \
cp environment/.env.example environment/.env.testing_docker && \
cp environment/env.sample.php environment/env.php && \
sed -i "s/dev/dev_docker/g" environment/env.php

echo "$date Memory Limit"
## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
## Mac's sed idiosyncrasies :(
echo 'memory_limit = 128M' | sed -E 's/memory_limit\s*=\s*\d*M/memory_limit = 3048M/g' /etc/php7/php.ini > /tmp/php.ini
mv /tmp/php.ini /etc/php7/php.ini


echo "$date Starting Nginx"
export PATH=$PATH:/app/

echo "$date Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'


