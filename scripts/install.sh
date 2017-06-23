#!/bin/bash
set -euo pipefail

# Deployment Script
echo "Setting BASEDIR"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
API_INSTALL_DIR="/home/ubuntu/api"
ALOHOMORA_BIN="$(which alohomora)"

# Install new version
echo  "Install new version"
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ "$API_INSTALL_DIR"

# Fix permissions
echo  "Fix permissions"
cd "$API_INSTALL_DIR" && sudo chmod 777 -R storage

# Run alohomora. No DB command should be run before this step
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$GIMLI_INSTALL_DIR/environment/.env.vault.j2"
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$GIMLI_INSTALL_DIR/environment/env.php.j2"

# DB Migrate
echo  "DB Migrate"
cd "$API_INSTALL_DIR" && php artisan migrate --force && php artisan migrate --database=test --force

# Restart all queue worker processes
cd "$API_INSTALL_DIR" && php artisan queue:restart
