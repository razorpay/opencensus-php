#!/bin/bash

# wait for db to be provisioned
sleep 30

# Fix permissions
echo  "$(date) Fix permissions"
cd /app/ && chmod 777 -R storage

# Copy config
echo "$(date) Configuring App"
cp dockerconf/api.docker.conf /etc/apache2/conf.d/api.conf && \
cp environment/.env.sample environment/.env.docker && \
cp environment/env.sample.php environment/env.php

# Change env to dev_docker
sed -i 's/dev/dev_docker/g' environment/env.php

# Common changes for the app, db and redis hosts, cache drivers
#
sed -i 's/^APP_URL="https://api.razorpay.com"/#APP_URL="https://api.razorpay.com"/' environment/.env.docker
sed -i 's/^APP_HOST="api.razorpay.com"/#APP_HOST="api.razorpay.com"/' environment/.env.docker

# Now create dev_docker and testing_docker
cp environment/.env.docker environment/.env.dev_docker && \
cp environment/.env.docker environment/.env.testing_docker

# Set DB name for testing
sed -i 's/^DB_LIVE_DATABASE=api_live/DB_LIVE_DATABASE=api_testing_live/' environment/.env.testing_docker
sed -i 's/^DB_TEST_DATABASE=api_test/DB_TEST_DATABASE=api_testing_test/' environment/.env.testing_docker
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

# DB Migrate
echo  "$(date) DB Migrate"
echo "$(date) Seeding live database"
cd /app/ && \
php artisan rzp:dbr --install --seed
echo "$(date) Seeding Test database"
APP_ENV=testing_docker php artisan rzp:dbr --install
echo "$(date) Starting Apache"
export PATH=$PATH:/app/:/app/vendor/bin/

# start httpd
echo "$(date) Apache"
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/httpd -D FOREGROUND
