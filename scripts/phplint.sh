#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

for dir in app config public resources; do
	find $dir -iname "*.php" | xargs -n1 php -l
done


