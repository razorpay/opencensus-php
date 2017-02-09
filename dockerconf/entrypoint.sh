#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
cp environment/.env.docker environment/.env.dev && \
cp environment/.env.docker environment/.env.testing && \
cp environment/env.sample.php environment/env.php && \
sed -i 's/DB_LIVE_DATABASE=api_live/DB_LIVE_DATABASE=api_testing_live/g' environment/.env.testing
sed -i 's/DB_TEST_DATABASE=api_test/DB_TEST_DATABASE=api_testing_test/g' environment/.env.testing 

## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
## Mac's sed idiosyncrasies :(
echo 'memory_limit = 128M' | sed -E 's/memory_limit\s*=\s*\d*M/memory_limit = 3048M/g' /etc/php7/php.ini > /tmp/php.ini
mv /tmp/php.ini /etc/php7/php.ini

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
