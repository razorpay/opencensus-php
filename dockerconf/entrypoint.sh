#!/bin/bash

set -euo pipefail

if [[ -n "${GIT_COMMIT_HASH}" ]]; then
    echo "${GIT_COMMIT_HASH}" > /app/public/commit.txt
fi

cd /app/

if [[ "${APP_MODE}" == "dev" ]]
then
  cp environment/.env.docker environment/.env.dev
else
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "dockerconf/dashboard.conf.j2" "environment/env.php.j2"
fi

cp dockerconf/dashboard.conf /etc/nginx/conf.d/dashboard.conf
sed -i "s|NGINX_HOST|$HOSTNAME|g" dockerconf/dashboard.conf

# echo "$date Memory Limit"
## This is a bad workaround for increasing php's memory to to 3G enable running tests locally
## Mac's sed idiosyncrasies :(
# echo 'memory_limit = 128M' | sed -E 's/memory_limit\s*=\s*\d*M/memory_limit = 3048M/g' /etc/php7/php.ini > /tmp/php.ini
# mv /tmp/php.ini /etc/php7/php.ini

# Fix permissions
echo  "$date Fix permissions"
chmod 777 -R storage
echo "$date Configuring App"

echo "$(date) DB Migrate"
echo "$(date) Seeding live db"
php artisan migrate --seed

echo "$date Starting Nginx"
export PATH=$PATH:/app/

echo "$date Starting Nginx"
mkdir /tmp/run
chown 0775 /tmp/run/
/usr/sbin/php-fpm7
/usr/sbin/nginx -g 'daemon off;'
