#!/usr/bin/dumb-init /bin/sh

set -euo pipefail

# ref doc: <TODO>
term_to_winch() {
  echo "Caught SIGQUIT signal!"
  # Using CHILD=$! for initializing the pid, initializes it with the nginx pid.
  # Initializing CHILD with the nginx PID can cause a "pid not found" error and result in an abrupt termination of the pod.
  # To achieve a graceful termination, it is necessary to kill the php-fpm master process, which will subsequently terminate
  # the worker processes. Once all the php-fpm processes have been successfully terminated, the pod can be gracefully terminated.
  # To ensure this, a while loop has been included to check for any ongoing php-fpm processes before the function is completed.
  # If there are active processes, the loop will pause for 1 second before rechecking.
  CHILD=`pgrep "php-fpm: master process"`
  # We do this so before graceful shutdown we remove the pod from the service by failing the readiness probe.
  touch /app/public/graceful-shutdown.txt
  # Wait for readiness probe to fail so no additional requests are received
  sleep 12
  # Translate the SIGTERM we caught to a SIGQUIT for the child processes
  kill -s SIGQUIT "$CHILD"
  while pgrep "php-fpm"
  do
    sleep 1
  done
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

#export APP_ENV=$APP_MODE
#echo "running optimize in {$APP_ENV}"
php artisan optimize

echo "$(date) Copy dashboard vhost"


if [[ $devserve == "true" ]]; then
  cp dockerconf/dashboard-dev.conf /etc/nginx/conf.d/default.conf
else
  cp dockerconf/nginx.conf /etc/nginx/conf.d/default.conf
fi

export OPCACHE_ENABLE=1

echo "setting max_input_vars to 2000"
sed -ie "s/; max_input_vars =.*/max_input_vars = 2000/g" /etc/php81/php.ini
echo "opcache.enable=1" >> /etc/php81/php.ini
echo "opcache.jit_buffer_size=256M" >> /etc/php81/php.ini
echo "opcache.jit=tracing" >> /etc/php81/php.ini
echo "opcache.memory_consumption=128" >> /etc/php81/php.ini
echo "opcache.max_accelerated_files=10000" >> /etc/php81/php.ini

touch /etc/php81/conf.d/opcache.ini
echo "opcache.enable=1" >> /etc/php81/conf.d/opcache.ini
echo "opcache.revalidate_freq=0" >> /etc/php81/conf.d/opcache.ini
echo "opcache.validate_timestamps=1" >> /etc/php81/conf.d/opcache.ini
echo "opcache.max_accelerated_files=10000" >> /etc/php81/conf.d/opcache.ini
echo "opcache.memory_consumption=192" >> /etc/php81/conf.d/opcache.ini
echo "opcache.max_wasted_percentage=10" >> /etc/php81/conf.d/opcache.ini
echo "opcache.interned_strings_buffer=16" >> /etc/php81/conf.d/opcache.ini
echo "opcache.fast_shutdown=1" >> /etc/php81/conf.d/opcache.ini

export PATH=$PATH:/app/

echo "$(date) Starting Nginx"

# Any volume mounts must be chown-ed again
chown -R nginx:nginx /app/storage/logs

# /tmp needs to writable by all processes.
chmod 777 /tmp
chmod 777 /app/storage/logs

/usr/sbin/php-fpm81

/usr/sbin/nginx -g 'daemon off;'
