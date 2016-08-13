#!/bin/bash
# http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# Deployment Script
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
echo "BASEDIR=$BASEDIR"

# Take the app down
echo "== php artisan down == "
cd /home/ubuntu/dashboard/ && php artisan down

# Install new version
echo "== rsync start == "
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/dashboard/
echo "== rsync end == "

# Fix permissions
echo "== chmod == "
cd /home/ubuntu/dashboard/ && sudo chmod 777 -R storage

# DB Migrate
# force flag is required because the app is in production
echo "== php artisan migrate --force == "
cd /home/ubuntu/dashboard/ && php artisan migrate --force

# Take the app up
echo "== php artisan up == "
cd /home/ubuntu/dashboard/ && php artisan up
