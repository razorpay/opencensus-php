#!/usr/bin/dumb-init /bin/sh
set -euo pipefail

fix_permissions(){
  echo  "$(date) Fix permissions"
  cd /app/ && chmod 777 -R storage
}

configure(){
  ALOHOMORA_BIN=$(which alohomora)
  echo "casting alohomora - vault,env.php,apache"
  sed -i "s|APACHE_HOST|$HOSTNAME|g" dockerconf/api.apache.conf.j2
  $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "environment/.env.vault.j2" "environment/env.php.j2" "dockerconf/api.apache.conf.j2"
  echo "copying apache config"
  cp dockerconf/api.apache.conf /etc/apache2/conf.d/api.conf

  ## Enable newrelic only for prod and perf
  if [[ "${APP_MODE}" == "prod" ]] || [[ "${APP_MODE}" == "perf" ]]; then
    $ALOHOMORA_BIN cast --region ap-south-1 --env $APP_MODE --app api "dockerconf/newrelic.ini.j2"
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
}

start_apache(){
  echo "$(date) Starting Apache"
  export PATH=$PATH:/app/:/app/vendor/bin/
  # start httpd
  echo "$(date) Apache"
  mkdir /tmp/run
  chown 0775 /tmp/run/
  /usr/sbin/httpd -D FOREGROUND
}

initialize(){
  fix_permissions
  configure
}

### Check that atleast either webapp or supervisor is specified
if [ "$#" -eq 0 ]; then
    echo "Specify app type: < web | supervisor >"
    exit -1
fi

## Do the basic initialization and get the app type

function main {
  initialize
  app_type=$1

  ## Now, based on the app type, call the specific functions
  if [[ "${app_type}" == "web" ]]; then
    echo "Starting web app"
    start_apache
  elif [[ "${app_type}" == "web-dark" ]]; then
    configure_dark
    echo "Starting web app"
    start_apache
  elif [[ "${app_type}" == "sqs" ]]; then
    sleep_time=$2
    #['sqs', '10']
    if [ "$#" -ne 2 ]; then
        echo "Need to specify following args: "
        echo "sleep: <n seconds>"
        exit -1
    else
      echo "starting sqs listener"
      php artisan queue:work ${app_type} --sleep=${sleep_time}
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
      php artisan queue:work ${app_type} --queue=${APP_MODE}-${queue_name} --sleep=${sleep_time}
    fi
  fi

}

main $@
