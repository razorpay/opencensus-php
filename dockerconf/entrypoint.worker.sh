#!/bin/sh
set -euo pipefail

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

configure(){
  echo "casting alohomora - vault,env.php"

  #The dynamodb convention changes for dev-cluster and reading those value from env variable DYNAMODB_PREFIX from kubernetes deployment
  #APP_MODE can be changed to ephemeral but need to make changes in logging package as well
  if [[ "${APP_MODE}" == "ephemeral" ]]; then
    alohomora cast --region ap-south-1 --env "$APP_MODE" --app api "environment/env.php.j2"
    alohomora cast --region ap-south-1 --env "$DYNAMODB_PREFIX" --app api "environment/.env.ephemeral.j2"
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
  else
    alohomora cast --region ap-south-1 --env "$APP_MODE" --app api "environment/.env.vault.j2" "environment/env.php.j2"
  fi

  echo "setting max_input_vars to 2000"
  sed -ie "s/; max_input_vars =.*/max_input_vars = 2000/g" /etc/php81/php.ini
  echo "Route Cache"
  php artisan route:cache
}

run_migration_job(){
    cd /app
    php artisan migrate --database=live_migration --force && php artisan migrate --database=test_migration --force
    php artisan migrate --database=payments_upi_live --path=database/migrations/payments_upi --force
    php artisan migrate --database=payments_upi_test --path=database/migrations/payments_upi --force
    php artisan migrate --database=live_migration --path=database/migrations/p2p --force
    php artisan migrate --database=test_migration --path=database/migrations/p2p --force
}

initialize(){
  # in case of container restart, this file might still exist.
  # this file is used to remove proxysql after this container's execution is completed.
  rm -f /container-share/sigterm-check.txt
  fix_permissions
  configure
}

change_db_user_for_workers() {
  vault_file=/app/environment/.env.vault

  # We want queue workers to use a different app user so that we can debug the connections better.
  # The below code will change the username of the app user to api_worker/api_test_worker.
  # Please note, that the password for web & queue app users needs to be the same,
  # as we are NOT changing the password in the below code.
  if [[ "${APP_MODE}" == "prod" ]]; then
    sed -i "s/SLAVE_DB_LIVE_USERNAME .*/SLAVE_DB_LIVE_USERNAME=api_worker_replica/g" $vault_file
    sed -i "s/SLAVE_DB_TEST_USERNAME .*/SLAVE_DB_TEST_USERNAME=api_worker_test_replica/g" $vault_file
    sed -i "s/DB_LIVE_USERNAME .*/DB_LIVE_USERNAME=api_worker/g" $vault_file
    sed -i "s/DB_TEST_USERNAME .*/DB_TEST_USERNAME=api_test_worker/g" $vault_file
  elif [[ "${APP_MODE}" == "automation" ]] || [[ "${APP_MODE}" == "func" ]] || [[ "${APP_MODE}" == "bvt" ]] || [[ "${APP_MODE}" == "availability" ]] || [[ "${APP_MODE}" == "perf" ]] || [[ "${APP_MODE}" == "perf1" ]] || [[ "${APP_MODE}" == "perf2" ]]; then
    sed -i "s/SLAVE_DB_LIVE_USERNAME .*/SLAVE_DB_LIVE_USERNAME=${APP_MODE}_api_worker/g" $vault_file
    sed -i "s/SLAVE_DB_TEST_USERNAME .*/SLAVE_DB_TEST_USERNAME=${APP_MODE}_api_test_worker/g" $vault_file
    sed -i "s/DB_LIVE_USERNAME .*/DB_LIVE_USERNAME=${APP_MODE}_api_worker/g" $vault_file
    sed -i "s/DB_TEST_USERNAME .*/DB_TEST_USERNAME=${APP_MODE}_api_test_worker/g" $vault_file
  elif [[ "${APP_MODE}" == "perf1" ]]; then
    sed -i "s/SLAVE_DB_LIVE_USERNAME .*/SLAVE_DB_LIVE_USERNAME=perf_api_worker/g" $vault_file
    sed -i "s/SLAVE_DB_TEST_USERNAME .*/SLAVE_DB_TEST_USERNAME=perf_api_test_worker/g" $vault_file
    sed -i "s/DB_LIVE_USERNAME .*/DB_LIVE_USERNAME=perf_api_worker/g" $vault_file
    sed -i "s/DB_TEST_USERNAME .*/DB_TEST_USERNAME=perf_api_test_worker/g" $vault_file
  elif [[ "${APP_MODE}" == "devserve" ]]; then
    # skipping as the secrets are injected via kube secrets
    continue
  else
    sed -i "s/SLAVE_DB_LIVE_USERNAME .*/SLAVE_DB_LIVE_USERNAME=api_${APP_MODE}_worker/g" $vault_file
    sed -i "s/SLAVE_DB_TEST_USERNAME .*/SLAVE_DB_TEST_USERNAME=api_${APP_MODE}_test_worker/g" $vault_file
    sed -i "s/DB_LIVE_USERNAME .*/DB_LIVE_USERNAME=api_${APP_MODE}_worker/g" $vault_file
    sed -i "s/DB_TEST_USERNAME .*/DB_TEST_USERNAME=api_${APP_MODE}_test_worker/g" $vault_file
  fi
}

create_kafka_credentials_dir() {
  mkdir -p /opt/razorpay/certs/kafka
  chown 0775 /opt/razorpay/certs/kafka
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

  if [[ "${app_type}" == "migrations-job" ]]; then
    echo "Starting db migration job"
    run_migration_job
  elif [[ "${app_type}" == "batch-job" ]]; then
    echo "Starting K8s Job"
    create_kafka_credentials_dir
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
  elif [[ "${app_type}" == "kafka-consumer" ]]; then
    echo "Starting Kafka Consumer Job"
    command=$2
    mode=$3
    topics=$4
    create_kafka_credentials_dir
    php artisan "${command}" "${mode}" "${APP_MODE}-${topics}"
  elif [[ "${app_type}" == "kafka-general-consumer" ]]; then
    echo "Starting Kafka general Consumer Job"
    mode=$5
    groupId=$4
    consumer=$3
    topics=$2
    create_kafka_credentials_dir
    php artisan kafka-consumer:consume --topics="${topics}" --consumer="${consumer}" --groupId="${groupId}" --mode="${mode}"
  elif [[ "${app_type}" == "sqs" ]]; then
    change_db_user_for_workers
    sleep_time=$2
    #['sqs', '10']
    if [ "$#" -ne 2 ]; then
        echo "Need to specify following args: "
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      create_kafka_credentials_dir
      if [[ "${APP_MODE}" == "devserve" ]]; then
        tail -F storage/logs/$HOSTNAME-trace-$(date +%Y-%m-%d).log &
        php artisan queue:listen "${app_type}" --tries=0 --sleep="${sleep_time}"
      else
        php artisan queue:work "${app_type}" --tries=0 --sleep="${sleep_time}"
      fi
    fi
  elif [[ "${app_type}" == "sqs_multi_default" ]]; then
    change_db_user_for_workers
    queue_name=$2
    sleep_time=$3
    if [ "$#" -ne 3 ]; then
        echo "Need to specify following args: "
        echo "queue: <sqs-name>"
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      create_kafka_credentials_dir
      if [[ "${APP_MODE}" == "devserve" ]]; then
        tail -F storage/logs/$HOSTNAME-trace-$(date +%Y-%m-%d).log &
        php artisan queue:listen "${app_type}" --tries=0 --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
      else
        php artisan queue:work "${app_type}" --tries=0 --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
      fi
    fi
  elif [[ "${app_type}" == "sqs-raw" ]]; then
    change_db_user_for_workers
    queue_name=$2
    sleep_time=$3
    if [ "$#" -ne 3 ]; then
        echo "Need to specify following args: "
        echo "queue: <sqs-name>"
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs-raw listener"
      create_kafka_credentials_dir
      if [[ "${APP_MODE}" == "devserve" ]]; then
        tail -F storage/logs/$HOSTNAME-trace-$(date +%Y-%m-%d).log &
        php artisan queue:listen "${app_type}" --tries=0 --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
      else
        php artisan queue:work "${app_type}" --tries=0 --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
      fi
    fi
  elif [[ "${app_type}" == "sqs-fifo" ]]; then
    change_db_user_for_workers
    queue_name=$2
    sleep_time=$3
    if [ "$#" -ne 3 ]; then
        echo "Need to specify following args: "
        echo "queue: <sqs-name>"
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs-fifo listener"
      create_kafka_credentials_dir
      php artisan queue:work "${app_type}" --tries=0 --queue="${APP_MODE}-${queue_name}" --sleep="${sleep_time}"
    fi
  else
    echo "Invalid entrypoint arguments in worker container."
    exit -1
  fi
}

main "$@"
