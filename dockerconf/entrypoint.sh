#!/usr/bin/dumb-init /bin/sh

set -euo pipefail

# ref doc: <TODO>
term_to_winch() {
  echo "Caught SIGQUIT signal!"
  # We do this so before graceful shutdown we remove the pod from the service by failing the readiness probe.
  touch /app/public/graceful-shutdown.txt
  # Wait for readiness probe to fail so no additional requests are received
  sleep 12
  # Translate the SIGTERM we caught to a SIGWINCH for the child processes
  kill -s SIGQUIT "$CHILD"
  wait "$CHILD"
  echo "Child exited"
}

trap term_to_winch SIGQUIT
echo "$(date) Cast config for environments"
# casting only env.php.j2 for devserve env as the secrets are injected via kube secrets
# DEV_SERVE variable is set
devserve="${DEV_SERVE:-false}"
if [[ $devserve == "true" ]]; then
  bash /app/dockerconf/admin-files-downloader.sh
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/env.php.j2"
  # log the output into stdout as php monolog has a bug in logging
  # Note this is enabled only for devstack for easing the debugging but should STRICTLY be avoided in any other environement as tail will run as a background process
  tail -F storage/logs/$HOSTNAME-trace.log &
else
  alohomora cast --region ap-south-1 --env $APP_MODE --app dashboard "environment/.env.vault.j2" "environment/env.php.j2"
fi

echo "$(date) Copy dashboard vhost"
cp dockerconf/nginx.conf /etc/nginx/conf.d/default.conf

echo "setting max_input_vars to 2000"
sed -ie "s/; max_input_vars =.*/max_input_vars = 5000/g" /etc/php7/php.ini

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

# /tmp needs to writable by all processes.
chmod 777 /tmp

/usr/sbin/php-fpm7

/usr/sbin/nginx -g 'daemon off;'
