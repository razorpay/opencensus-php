#!/bin/bash

set -euo pipefail
cd /app/

if [[ "${APP_MODE}" == "dev" ]]
then
  cp environment/.env.docker environment/.env.dev
else
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "environment/env.php.j2"
fi

cp dockerconf/dashboard.conf /etc/nginx/conf.d/dashboard.conf

echo "$(date) DB Migrate"
echo "$(date) Seeding live db"
php artisan migrate --seed

echo "$(date) Starting Nginx"
export PATH=$PATH:/app/

echo "$(date) Starting Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/

/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'
