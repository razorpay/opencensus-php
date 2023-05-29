#!/bin/sh
set -euo pipefail

# Apache exits abruptly on SIGTERM and SIGWINCH has to be sent for it to gracefully stop.
# This should only run on apache starts not during queue jobs
term_to_winch() {
  echo "Caught SIGTERM signal!"
  # We do this so before graceful shutdown we remove the pod from the service by failing the readiness probe.
  rm -f /app/public/commit.txt
  # Wait for readiness probe to fail so no additional requests are received
  sleep 12
  # Translate the SIGTERM we caught to a SIGWINCH for the child processes
  kill -s SIGWINCH "$CHILD"
  wait "$CHILD"
  echo "Child exited"
}

trap finish EXIT

finish() {
  if [ -d "/container-share" ]
  then
    touch /container-share/sigterm-check.txt
  fi
}

fix_permissions() {
  echo "$(date) Fix permissions"
  cd /app/ && chmod 777 -R storage
}

create_kafka_credentials_dir() {
  mkdir -p /opt/razorpay/certs/kafka
  chmod 777 /opt/razorpay/certs -R
}

configure(){
  echo "casting alohomora - vault,env.php,apache"
  sed -i "s|APACHE_HOST|$HOSTNAME|g" dockerconf/api.apache.conf.j2

  #The dynamodb convention changes for dev-cluster and reading those value from env variable DYNAMODB_PREFIX from kubernetes deployment
  #APP_MODE can be changed to ephemeral but need to make changes in logging package as well
  if [[ "${APP_MODE}" == "ephemeral" ]]; then
    alohomora cast --region ap-south-1 --env "$APP_MODE" --app api "environment/env.php.j2"
    alohomora cast --region ap-south-1 --env "$DYNAMODB_PREFIX" --app api "dockerconf/api.apache.conf.j2" "environment/.env.ephemeral.j2"
   # We're casting env.ephemeral.j2 only when APP_MODE is ephemeral, in other scenarios the flow will be as usual.
   # Doing this will avoid us from resolving the different environmental conditions existing in vault.j2.
   # env.vault.j2 will remain unresolved in case of APP_MODE = 'ephemeral'
  elif [[ "${APP_MODE}" == "devserve" ]] ; then
    # casting only env.php.j2 and apache conf for devserve env as the secrets are injected via kube secrets
    # DEV_SERVE variable is to be passed as true
    # creating /var/log/apache as we config in apache2 folder but using apache for logging which is mounted in kubernetes
    mkdir -p /var/log/apache/
    chown 0775 /var/log/apache/
    alohomora cast --region ap-south-1 --env "$APP_MODE" --app api "environment/env.php.j2" "dockerconf/api.apache.conf.j2"
     # log the output into stdout as php monolog has a bug in logging
    tail -F storage/logs/$HOSTNAME-trace-$(date +%Y-%m-%d).log &
 else
    alohomora cast --region ap-south-1 --env "$APP_MODE" --app api "environment/.env.vault.j2" "environment/env.php.j2" "dockerconf/api.apache.conf.j2"
  fi

  echo "copying apache config"
  cp dockerconf/api.apache.conf /etc/apache2/conf.d/api.conf
  sed -i 's/^#ExtendedStatus\ On/ExtendedStatus\ On/g' /etc/apache2/conf.d/info.conf
  sed -i 's/^ServerSignature\ On/ServerSignature Off/g' /etc/apache2/httpd.conf
  echo "setting max_input_vars to 2000"
  sed -ie "s/; max_input_vars =.*/max_input_vars = 2000/g" /etc/php81/php.ini
  echo "Route Cache"
  php artisan route:cache
}

configure_dark(){
    ## Configure queue workers for dark
    ## Ref: https://github.com/razorpay/api/blob/master/scripts/install.sh#L49
    cd /app
    echo "== Queue on Sync driver =="
    echo QUEUE_DRIVER=sync >> ./environment/.env.production
    echo SLACK_QUEUE_DRIVER=sync >> ./environment/.env.production
    echo "MOZART_URL=\"https://mozart-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "MOZART_TEST_URL=\"https://mozart-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "MOZART_LIVE_URL=\"https://mozart-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "SCROOGE_URL=\"https://scrooge-dark.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "SCROOGE_DCS_BASE_URL=\"https://scrooge-dark.razorpay.com\"" >> ./environment/.env.production
    echo "CORE_PAYMENT_SERVICE_LIVE_URL=\"https://cps-dark-live.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CORE_PAYMENT_SERVICE_TEST_URL=\"https://cps-dark-test.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_LIVE_URL=\"https://payments-card-dark-int.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_TEST_URL=\"https://payments-card-test-dark.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "NBPLUS_PAYMENT_SERVICE_LIVE_URL=\"https://payments-nbplus-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "NBPLUS_PAYMENT_SERVICE_TEST_URL=\"https://payments-nbplus-test-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "UPI_PAYMENT_SERVICE_LIVE_URL=\"https://payments-upi-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "UPI_PAYMENT_SERVICE_TEST_URL=\"https://payments-upi-test-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "CHECKOUT_URL=\"https://checkout-dark.razorpay.com\"" >> ./environment/.env.production
    echo "TERMINALS_SERVICE_TEST_URL=\"https://terminals-dark-test.razorpay.com/\"" >> ./environment/.env.production
    echo "TERMINALS_SERVICE_LIVE_URL=\"https://terminals-dark-live.razorpay.com/\"" >> ./environment/.env.production
    echo "PG_ROUTER_URL=\"https://pg-router-dark-int.razorpay.com/\"" >> ./environment/.env.production
    echo "CARD_VAULT_URL=\"https://vault-dark.razorpay.com/v1/\"" >> ./environment/.env.production
}

configure_hallmark(){
    cd /app
    echo SLACK_QUEUE_DRIVER=sync >> ./environment/.env.production
    echo "MOZART_URL=\"https://mozart-hallmark.razorpay.com/\"" >> ./environment/.env.production
    echo "MOZART_TEST_URL=\"https://mozart-test-hallmark.razorpay.com/\"" >> ./environment/.env.production
    echo "MOZART_LIVE_URL=\"https://mozart-hallmark.razorpay.com/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_LIVE_URL=\"https://payments-card-live-hallmark.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_TEST_URL=\"https://payments-card-test-hallmark.razorpay.com/v1/\"" >> ./environment/.env.production
}


start_apache(){
  trap term_to_winch SIGTERM
  echo "$(date) Starting Apache"
  export PATH=$PATH:/app/:/app/vendor/bin/
  # start httpd
  echo "$(date) Apache"
  mkdir /tmp/run
  chown 0775 /tmp/run/
  /usr/sbin/httpd -D FOREGROUND &
  CHILD=$!
  wait "$CHILD"
}

initialize(){
  # in case of container restart, this file might still exist.
  # this file is used to remove proxysql after this container's execution is completed.
  rm -f /container-share/sigterm-check.txt
  fix_permissions
  configure
}

### Check that atleast either webapp or supervisor is specified
if [ "$#" -eq 0 ]; then
    echo "Specify app type: < web | web-dark | batch-job | sqs | sqs_multi_default >"
    exit -1
fi

## Do the basic initialization and get the app type
main() {
  initialize
  app_type=$1
  # This is used as the readiness probe for
  # non-web deployments, such as queues
  # php artisan queue workers terminate gracefully when
  # SIGTERM is passed to them: https://github.com/illuminate/queue/blob/fa963ecc830b13feb4d2d5f154b8a280a1c23aa2/Worker.php#L522-L529
  touch /app/ready

  ## Now, based on the app type, call the specific functions
  if [[ "${app_type}" == "web" ]]; then
    echo "Starting web app"
    create_kafka_credentials_dir
    start_apache
  elif [[ "${app_type}" == "web-dark" ]]; then
    configure_dark
    echo "Starting web app"
    create_kafka_credentials_dir
    start_apache
  elif [[ "${app_type}" == "web-hallmark" ]]; then
    configure_hallmark
    create_kafka_credentials_dir
    echo "Starting web app"
    start_apache
  else
    echo "Invalid entrypoint arguments in web container."
    exit -1
  fi
}

main "$@"
