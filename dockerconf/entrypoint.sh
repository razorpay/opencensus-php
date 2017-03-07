#!/bin/bash

echo "Starting Xvfb"
Xvfb :0 -ac -screen 0 1280x800x24 &

export DISPLAY=:0
export CHROMIUM_BIN=/usr/bin/chromium

export DB_MYSQL_HOST=localhost
export DB_MYSQL_PORT=3306
export DB_MYSQL_DATABASE=dashboard
export DB_MYSQL_USERNAME=root
export DB_MYSQL_PASSWORD=

echo "Starting selenium server"
/selenium &> /dev/null &

# echo "Starting vnc server"
# x11vnc &> /dev/null &

# echo "Starting fluxbox"
# fluxbox &> /dev/null &

echo "Starting mysql"
service mysql start

echo "creating db"
mysql -e "CREATE DATABASE IF NOT EXISTS dashboard;"

echo "Executing command $@"
exec "$@"
