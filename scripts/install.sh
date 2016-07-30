#!/bin/bash
# http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# Deployment Script
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )

# Take the app down
cd /home/ubuntu/dashboard/ && php artisan down

# Install new version
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/dashboard/

# Fix permissions
cd /home/ubuntu/dashboard/ && sudo chmod 777 -R storage

# DB Migrate
cd /home/ubuntu/dashboard/ && php artisan migrate

# Take the app up
cd /home/ubuntu/dashboard/ && php artisan up
