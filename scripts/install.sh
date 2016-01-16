#!/bin/bash
# Deployment Script
echo "Setting BASEDIR"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )

# Take the app down
echo "Take the app down"
cd /home/ubuntu/api/ && php artisan down

# Install new version
echo  "Install new version"
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/api/

# Fix permissions
echo  "Fix permissions"
cd /home/ubuntu/api/ && sudo chmod 777 -R app/storage

# DB Migrate
echo  "DB Migrate"
cd /home/ubuntu/api/ && php artisan migrate --force && php artisan migrate --database=test --force

# Take the app up
echo  "Take the app up"
cd /home/ubuntu/api/ && php artisan up