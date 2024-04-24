#!/bin/sh
set -euo pipefail

alohomora cast --region ap-south-1 --env "$APP_MODE" --app api ".env.vault.j2"

if [ $? -eq 0 ] ;then

    echo "Casting of alohomora successful for credstash-$APP_MODE-api and INSTANCE_TYPE - $INSTANCE_TYPE"

    mv ./.env.vault ./vault

    echo "File moved to vault"

    # Split the content into secrets of 50KB each
    split -b 50k vault secret_

    count=0

    # Apply each secret as a separate patch
    for secret_file in secret_*; do
        KEY=$(basename "$secret_file")
        VALUE=$(cat "$secret_file" | base64 | tr -d '\n')
        count=$((count + 1))
        kubectl patch secret "$SECRET_NAME" --type='json' -p="[{\"op\": \"replace\", \"path\": \"/data/$KEY\", \"value\": \"$VALUE\"}]"
    done
    count=$(printf "%s" "$count" | base64 | tr -d '\n')
    kubectl patch secret "$SECRET_NAME" --type='json' -p="[{\"op\": \"replace\", \"path\": \"/data/count\", \"value\": \"$count\"}]"

    # Remove temporary secret files
    rm secret_*
    exit 0
fi

echo "Casting of alohomora failed for credstash-$APP_MODE-api and INSTANCE_TYPE $INSTANCE_TYPE"
exit 1
