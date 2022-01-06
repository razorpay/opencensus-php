#!/bin/sh
# Triggers rundeck's webhook to execute e2e tests.

# Arguments
COMMIT=$1

# Variables
# These services with their corresponding images will be brought up on devstack for testing.
SERVICES="api"
IMAGES=$COMMIT
RUNDECK_WEBHOOK_URL="https://rundeck.dev.razorpay.in/api/40/webhook/Me83BPxssPQzGJQVMeEX4j2VaFTEklNc#E2E_Test_Execution"

# Makes request.
RESPONSE=$(
    curl -X POST -s -i \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        -d '{"commit_id": "'"$COMMIT"'", "services": "'"$SERVICES"'", "images": "'"$IMAGES"'"}' \
        $RUNDECK_WEBHOOK_URL
)

echo "$RESPONSE"

# Returns 0 if response code is 200.
echo "$RESPONSE" | grep -q "^HTTP/1.1 200"
