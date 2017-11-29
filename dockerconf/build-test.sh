#!/bin/sh

function sqlite_create {
  touch api_sqlite.db
}

function mysql_install {
    addgroup -S mysql \
    && adduser -S mysql -G mysql \
    && apk add --no-cache mysql mysql-client \
    && rm -f /var/cache/apk/* \
    && awk '{ print } $1 ~ /\[mysqld\]/ && c == 0 { c = 1; print "skip-host-cache\nskip-name-resolve\nlower_case_table_names=1\nskip-networking"}' /etc/mysql/my.cnf > /tmp/my.cnf \
    && mv /tmp/my.cnf /etc/mysql/my.cnf \
    && mkdir /run/mysqld \
    && chown -R mysql:mysql /run/mysqld \
    && chmod -R 777 /run/mysqld

    if [ ! -d /var/lib/mysql/mysql ]; then
        echo 'Initializing database'
        mysql_install_db --user=mysql --rpm > /dev/null
        echo 'Database initialized'

        # Start MySQL
        /usr/bin/mysqld --user=mysql &
        mysql_pid="$!"

        sleep 10

        # Wait for MySQL to start
        for i in {30..0}; do
            if "/usr/bin/mysql --protocol=socket --user root -e 'SELECT 1'" &> /dev/null; then
                break
            fi
            echo 'MySQL init process in progress...'
            sleep 1
        done
        if [ "$i" = 0 ]; then
            echo >&2 'MySQL init process failed.'
            exit 1
        fi

        echo 'MySQL init process success...'

        echo "Securing mysql with password based access"
        ## Simulate mysql_secure_installation
        mysql --user=root <<_EOF_
        UPDATE mysql.user SET Password=PASSWORD('root') WHERE User='root';
        DELETE FROM mysql.user WHERE User='';
        DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
        DROP DATABASE IF EXISTS test;
        DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
        FLUSH PRIVILEGES;
_EOF_

    fi
    echo "Creating test database"
    /usr/bin/mysql --protocol=socket --user root -proot < /app/dockerconf/api_db.sql
    echo "Mysql server is up with status $?"
}

function es_install()
{
    echo "Install Elastic Search"
    apk update && apk add curl openjdk8-jre && \
    curl -o /usr/local/bin/gosu -sSL "https://github.com/tianon/gosu/releases/download/1.7/gosu-amd64" && \
    chmod +x /usr/local/bin/gosu && \
    curl -o elasticsearch-5.4.1.tar.gz -sL https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-5.4.1.tar.gz &&\
    tar -xzf elasticsearch-5.4.1.tar.gz && \
    rm elasticsearch-5.4.1.tar.gz && \
    mv elasticsearch-5.4.1 /usr/share/elasticsearch && \
    mkdir -p /usr/share/elasticsearch/data /usr/share/elasticsearch/logs /usr/share/elasticsearch/config/scripts && \
    adduser -DH -s /sbin/nologin elasticsearch && \
    chown -R elasticsearch:elasticsearch /usr/share/elasticsearch

    ## Start Elastic Search
    echo "Starting Elastic Search"
    gosu elasticsearch sh /usr/share/elasticsearch/bin/elasticsearch &

    sleep 10

    ## Check for ES status
    url='http://localhost:9200'
    condition=1
    echo "Doing Status Check for Elasticsearch URL:" $url
    while [ $condition -eq 1 ]
    do
        status=`curl -s -I "$url" 2>/dev/null | head -n 1|cut -d ' ' -f2`
        if [ -z "$status" ]
        then
            echo "Elasticsearch starting. Waiting for server to startup and connect..."
            sleep 5
        else
            echo "Elasticsearch Server is operationally up at:" $url
            break
        fi
    done
    echo "Elasticsearch Server is operationally up at:" $url
}

function redis_install()
{
    echo "Installing redis"
    apk add redis
    ## Start redis
    mkdir /data && chown redis:redis /data && cd /data && gosu redis redis-server &
    ## TODO: check status here
    echo "Started redis"
}

function cleanup()
{
    rm -rf /var/cache/apk/*
    rm api_sqlite.db
}

function create_assertion_file {
  file=/etc/php7/conf.d/assertion.ini
  echo "zend.assertions=1" >> ${file}
  echo "assert.exception=On" >> ${file}
}

function nanotime_extension {
  file=/etc/php7/conf.d/nanotime.ini
  echo "extension=nanotime.so" >> ${file}
  cp /app/scripts/nanotime.so /usr/lib/php7/modules/nanotime.so
  echo "Listing extensions"
  php -m
}

function create_cert_dirs {
  mkdir -p /opt/razorpay/certs/first_data
}

function run_tests()
{
    create_assertion_file
    nanotime_extension
    create_cert_dirs

    cp /app/environment/.env.distelli /app/environment/.env.testing
    cd /app
    ## Start and run the tests
    export APP_MODE=dev
    php -d memory_limit=512M vendor/phpunit/phpunit/phpunit --debug --verbose
    exit $?
}

sqlite_create
mysql_install
es_install
redis_install
cleanup
run_tests
