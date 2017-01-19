#!/bin/bash

# wait for db to be provisioned
sleep 60

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
cp environment/.env.docker environment/.env.testing && \
cp environment/env.sample.php environment/env.php && \
sed -i 's/dev/testing/g' ./environment/env.php

# DB Migrate
echo  "DB Migrate"
cd /app/ && \
php artisan migrate --force && \
php artisan rzp:dbr --install --seed && \
APP_ENV=testing php artisan rzp:dbr --install

# start httpd
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/httpd -D FOREGROUND
