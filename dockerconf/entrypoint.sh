#!/bin/bash

set -euo pipefail
cd /app/

ALOHOMORA_BIN=$(which alohomora)
$ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "environment/env.php.j2" "dockerconf/dashboard.conf.j2" "dockerconf/newrelic.ini.j2"

echo "$(date) Add nginx host to dashboard."
sed -i "s|NGINX_HOST|$HOSTNAME|g" dockerconf/dashboard.conf

## Enable newrelic only for prod
if [[ "${APP_MODE}" == "prod" ]]; then
  echo "$(date) Cast newrelic config"
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "dockerconf/newrelic.ini.j2"

  echo "$(date) Copy newrelic config"
  cp dockerconf/newrelic.ini /etc/php7/conf.d/newrelic.ini
fi

echo "$(date) Copy dashboard to default."
cp dockerconf/dashboard.conf /etc/nginx/conf.d/default.conf

echo "$(date) DB Migrate"
echo "$(date) Seeding live db"
php artisan migrate --seed

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

/usr/sbin/php-fpm7

# Moving these logs to after the php-fpm7 creation.
chmod 644 /var/log/phpfpm/php7.0-fpm.log

/usr/sbin/nginx -g 'daemon off;'
