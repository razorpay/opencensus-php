#!/bin/bash
# Deployment Script
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )

# Take the app down
cd /home/ubuntu/api/ && php artisan down

# Install new version
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/api/

# Fix permissions
cd /home/ubuntu/api/ && sudo chmod 777 -R app/storage

# DB Migrate
cd /home/ubuntu/api/ && php artisan migrate --force && php artisan migrate --database=test --force

# Take the app up
cd /home/ubuntu/api/ && php artisan up