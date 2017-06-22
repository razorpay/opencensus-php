#!/bin/bash
# Deployment Script
echo "Setting BASEDIR"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )

# Install new version
echo  "Install new version"
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/api/

# Fix permissions
echo  "Fix permissions"
cd /home/ubuntu/api/ && sudo chmod 777 -R storage

# DB Migrate
echo  "DB Migrate"
cd /home/ubuntu/api/ && php artisan migrate --force && php artisan migrate --database=test --force

# Restart all queue worker processes
cd /home/ubuntu/api/ && php artisan queue:restart
