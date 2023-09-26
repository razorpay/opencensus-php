#!/bin/sh

app="app"

if [ "$1" = "$app" ]; then
  find . -type f -iname "*.php" | grep "./app/" | xargs -n1 php -l
else
  find . -type f -iname "*.php" | grep -v "./app/" | xargs -n1 php -l
fi
