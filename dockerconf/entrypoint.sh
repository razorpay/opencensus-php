#!/usr/bin/dumb-init /bin/sh

set -euo pipefail

## Enable newrelic only for prod
if [[ "${APP_MODE}" == "prod" ]]; then
  echo "$(date) Cast config"
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "dockerconf/newrelic.ini.j2" "environment/.env.vault.j2" "environment/env.php.j2"
  echo "$(date) Copy newrelic config"
  cp dockerconf/newrelic.ini /etc/php7/conf.d/newrelic.ini
else
  # This is mostly QA
  echo "$(date) Cast config"
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "environment/env.php.j2"
fi

echo "$(date) Copy dashboard vhost"
cp dockerconf/nginx.conf /etc/nginx/conf.d/default.conf

echo "$(date) DB Migrate"
echo "$(date) Seeding live db"
php artisan migrate --seed

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

# /tmp needs to writable by all processes.
chmod 777 /tmp

/usr/sbin/php-fpm7

# Moving these logs to after the php-fpm7 creation.
chmod 644 /var/log/phpfpm/php7.0-fpm.log

/usr/sbin/nginx -g 'daemon off;'
