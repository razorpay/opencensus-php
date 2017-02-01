#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
cp environment/.env.docker environment/.env.dev && \
cp environment/env.sample.php environment/env.php &&

# DB Migrate
echo  "DB Migrate"
echo "Seeding live database"
cd /app/ && \
php artisan rzp:dbr --install --seed 
echo "Seeding Test database"
APP_ENV=testing php artisan rzp:dbr --install
echo "Starting Apache"
export PATH=$PATH:/app/

# start httpd
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/httpd -D FOREGROUND
