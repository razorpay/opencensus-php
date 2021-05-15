#!/usr/bin/dumb-init /bin/sh

set -euo pipefail

echo "$(date) Cast config for environments"
# casting only env.php.j2 for devserve env as the secrets are injected via kube secrets
# DEV_SERVE variable is to be passed as true
if [[ "${DEV_SERVE}" == "true" ]]; then
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/env.php.j2"
else
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "environment/env.php.j2"
fi

echo "$(date) Copy dashboard vhost"
cp dockerconf/nginx.conf /etc/nginx/conf.d/default.conf

echo "setting max_input_vars to 2000"
sed -ie "s/; max_input_vars =.*/max_input_vars = 2000/g" /etc/php7/php.ini

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

# /tmp needs to writable by all processes.
chmod 777 /tmp

/usr/sbin/php-fpm7

/usr/sbin/nginx -g 'daemon off;'
