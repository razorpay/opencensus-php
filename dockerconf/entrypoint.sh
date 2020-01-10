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

fix_permissions(){
  echo  "$(date) Fix permissions"
  cd /app/ && chmod 777 -R storage
}

configure(){
  ALOHOMORA_BIN=$(which alohomora)
  echo "casting alohomora - vault,env.php,apache"
  sed -i "s|APACHE_HOST|$HOSTNAME|g" dockerconf/api.apache.conf.j2
  $ALOHOMORA_BIN cast --region ap-south-1 --env "$APP_MODE" --app api "environment/.env.vault.j2" "environment/env.php.j2" "dockerconf/api.apache.conf.j2"
  echo "copying apache config"
  cp dockerconf/api.apache.conf /etc/apache2/conf.d/api.conf

  ## Enable newrelic only for prod and perf
  if [[ "${APP_MODE}" == "prod" ]] || [[ "${APP_MODE}" == "perf" ]]; then
    $ALOHOMORA_BIN cast --region ap-south-1 --env "$APP_MODE" --app api "dockerconf/newrelic.ini.j2"
    cp dockerconf/newrelic.ini /etc/php7/conf.d/newrelic.ini
  fi

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
    echo "CORE_PAYMENT_SERVICE_LIVE_URL=\"https://cps-dark-live.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CORE_PAYMENT_SERVICE_TEST_URL=\"https://cps-dark-test.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_LIVE_URL=\"https://payments-card-dark.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "CARD_PAYMENT_SERVICE_TEST_URL=\"https://payments-card-test-dark.razorpay.com/v1/\"" >> ./environment/.env.production
    echo "NBPLUS_PAYMENT_SERVICE_LIVE_URL=\"https://payments-nbplus-dark.razorpay.com/\"" >> ./environment/.env.production
    echo "NBPLUS_PAYMENT_SERVICE_TEST_URL=\"https://payments-nbplus-test-dark.razorpay.com/\"" >> ./environment/.env.production
}

run_migration_job(){
    cd /app
    php artisan migrate --database=live_migration --force && php artisan migrate --database=test_migration --force
    php artisan migrate --database=payments_upi_live --path=database/migrations/payments_upi --force
    php artisan migrate --database=payments_upi_test --path=database/migrations/payments_upi --force
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
    start_apache
  elif [[ "${app_type}" == "web-dark" ]]; then
    configure_dark
    echo "Starting web app"
    start_apache
  elif [[ "${app_type}" == "migrations-job" ]]; then
    echo "Starting db migration job"
    run_migration_job
  elif [[ "${app_type}" == "batch-job" ]]; then
    echo "Starting K8s Job"
    command=$2
    batch_id=$3
    mode=$4
    php artisan "${command}" "${batch_id}" "${mode}"
  elif [[ "${app_type}" == "merchantInvoice-job" ]]; then
    echo "Starting K8s Job"
    command=$2
    mode=$3
    year=$4
    month=$5
    php artisan "${command}" "${mode}" "${year}" "${month}"
  elif [[ "${app_type}" == "sqs" ]]; then
    sleep_time=$2
    #['sqs', '10']
    if [ "$#" -ne 2 ]; then
        echo "Need to specify following args: "
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      php artisan queue:work "${app_type}" --sleep="${sleep_time}"
    fi
  elif [[ "${app_type}" == "sqs_multi_default" ]]; then
    queue_name=$2
    sleep_time=$3
    if [ "$#" -ne 3 ]; then
        echo "Need to specify following args: "
        echo "queue: <sqs-name>"
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      php artisan queue:work "${app_type}" --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
    fi
  fi

}

main "$@"
