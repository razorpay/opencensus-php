#!/bin/sh

run_bvt_suite_when_approved() {
  PRNumber=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
  commitId=$(jq --raw-output .pull_request.head.sha "$GITHUB_EVENT_PATH")
  skipRoast="false"
  if [ "${HOTFIX}" = "true" ] || [ "${REVERT}" = "true" ] || [ "${ONLY_IGNORE_FILES}" = "true" ]; then
    skipRoast="true"
  fi

  if [ -z "$roastPRCommit"]; then
    roastPRCommit="latest"
  fi

  if [ "$SKIP_ROAST_BY_DEV" != "false" ]; then
    SKIP_ROAST_BY_DEV="true"
  fi

  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  flag=false
  reviews=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/pulls/${PRNumber}/reviews?per_page=100" | jq --raw-output '.[] | {state: .state} | @base64')
  for r in $reviews; do
    review="$(echo "$r" | base64 -d)"
    rState=$(echo "$review" | jq --raw-output '.state')
    if [ "$rState" = "APPROVED" ]; then
      flag=true
    fi
  done
  if [ "$flag" = "false" ]; then
    echo "PR is not in Approved State Not Triggering Webhook"
    exit 0
  fi

  echo "Triggering bvt testing execution for : " + "$commitId"
  echo "Triggering bvt testing execution for Roast PR: " + "$roastPRCommit"
  echo "{\"CommitId\":\"$commitId\",\"skip_roast\":$skipRoast,\"PRNumber\":\"$PRNumber\",\"pr_status\":\"approved\",\"roast_commit_id\":\"$roastPRCommit\",\"skip_roast_by_dev\":$SKIP_ROAST_BY_DEV}"
  echo "https://deploy-github-actions.razorpay.com/webhooks/webhook/$WEBHOOK_TRIGGER"

  curl -X POST \
    -u github-actions:"$SPINNAKER_PASSWORD" \
    https://deploy-github-actions.razorpay.com/webhooks/webhook/$WEBHOOK_TRIGGER \
    -H "content-type: application/json" \
    -d "{\"CommitId\":\"$commitId\",\"skip_roast\":$skipRoast,\"PRNumber\":\"$PRNumber\",\"pr_status\":\"approved\",\"roast_commit_id\":\"$roastPRCommit\",\"skip_roast_by_dev\":$SKIP_ROAST_BY_DEV}"
}

run_bvt_suite_when_approved
