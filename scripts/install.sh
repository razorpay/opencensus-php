#!/bin/bash
# Deployment Script

# Take the app down
cd /home/ubuntu/api/ && php artisan down

# Install new version
rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ /home/ubuntu/api/

# Fix permissions
cd /home/ubuntu/api/ && sudo chmod 775 -R app/storage

# DB Migrate 
cd /home/ubuntu/api/ && php artisan migrate && php artisan migrate --database=test

# Take the app up
cd /home/ubuntu/api/ && php artisan up