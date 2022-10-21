#!/bin/bash

set -euo pipefail
cd /app/

cp environment/.env.dev_docker environment/.env.dev

echo "$(TZ=Asia/Pacific date) copy nginx config"
cp dockerconf/dashboard-dev.conf /etc/nginx/conf.d/default.conf

echo "Running composer install"
composer install

echo "DB Migrate"
echo "$(date) Seeding live db"
php artisan migrate --seed
php artisan key:generate

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

/usr/sbin/php-fpm81
/usr/sbin/nginx -g 'daemon off;'
