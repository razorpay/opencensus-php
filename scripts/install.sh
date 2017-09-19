#!/bin/bash
# http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# Deployment Script
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
DASHBOARD_INSTALL_DIR="/home/ubuntu/dashboard"
ALOHOMORA_BIN="$(which alohomora)"
echo "BASEDIR=$BASEDIR"

# Take the app down
echo "== php artisan down == "
cd $DASHBOARD_INSTALL_DIR && php artisan down

# Install new version
echo "== rsync start == "
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ $DASHBOARD_INSTALL_DIR
echo "== rsync end == "

# Fix permissions
echo "== chmod == "
cd $DASHBOARD_INSTALL_DIR && sudo chmod 777 -R storage

# Run alohomora. No DB command should be run before this step
# TODO: Upgrade to 0.4.0 and switch this to a single command
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$DASHBOARD_INSTALL_DIR/environment/.env.vault.j2"
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$DASHBOARD_INSTALL_DIR/environment/env.php.j2"

# DB Migrate
# force flag is required because the app is in production
echo "== php artisan migrate --force == "
cd $DASHBOARD_INSTALL_DIR && php artisan migrate --force

# Clear and Re-cache Routes
echo "== route cache =="
cd "$DASHBOARD_INSTALL_DIR" && php artisan route:cache

# Cache Config
echo "== Config Cache =="
cd "$DASHBOARD_INSTALL_DIR" && php artisan config:cache

# This clears the mod_php opcache
echo "== apache restart =="
sudo service apache2 restart

echo "== opcache cli clear =="
php $BASEDIR/scripts/clear_cli_opcache.php

# Restart all queue worker processes
# This ensures that our workers have the new code (and have cleared opcache)
echo "Queue Restart"
cd "$DASHBOARD_INSTALL_DIR" && php artisan queue:restart

# Take the app up
echo "== php artisan up == "
cd $DASHBOARD_INSTALL_DIR && php artisan up
