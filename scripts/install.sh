#!/bin/bash
set -euo pipefail

# Deployment Script
echo "Setting BASEDIR"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
API_INSTALL_DIR="/home/ubuntu/api"
ALOHOMORA_BIN="$(which alohomora)"

# TODO do this in a better way
# Fix permissions
echo  "Fix permissions for baseDir"
cd "$BASEDIR" && sudo chmod 777 -R storage

# Install new version
echo  "Install new version"
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ "$API_INSTALL_DIR"

# TODO remove this, as this is already done
# Fix permissions
echo  "Fix permissions"
cd "$API_INSTALL_DIR" && sudo chmod 777 -R storage

# Run alohomora. No DB command should be run before this step
echo  "Run alohomora"
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$API_INSTALL_DIR/environment/.env.vault.j2"
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$API_INSTALL_DIR/environment/env.php.j2"

# start supervisor as root
echo  "Supervisor Start"
sudo systemctl start supervisor

# DB Migrate
echo  "DB Migrate"
cd "$API_INSTALL_DIR" && php artisan migrate --force && php artisan migrate --database=test --force

# Restart all queue worker processes
echo "Queue Restart"
cd "$API_INSTALL_DIR" && php artisan queue:restart

# Clear and Re-cache Routes
echo "Route Cache"
cd "$API_INSTALL_DIR" && php artisan route:cache
