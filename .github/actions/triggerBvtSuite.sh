#!/bin/sh

run_bvt_suite_when_approved() {
  PRNumber=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
  commitId=$(jq --raw-output .pull_request.head.sha "$GITHUB_EVENT_PATH")
  skipRoast=${SKIP_ROAST}
  echo "Temporary logs for testing"
  if [ "$skipRoast" = "true" ]; then
    echo "Triggering skip regression webhook for bvt execution for :" + "$commitId"
    echo "Temporary logs for testing 2"
    curl -X POST \
      -u github-actions:"$SPINNAKER_PASSWORD" \
       https://deploy-github-actions.razorpay.com/webhooks/webhook/"$WEBHOOK_TRIGGER" \
       -H "content-type: application/json" \
       -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipRoast\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$PRNumber\",\"state\":\"approved\"} }"
    exit 0
  fi
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  (curl -c /tmp/cookies --location --request GET 'https://deploy-api.razorpay.com/login' \
  --header "Authorization: Bearer ${GITHUB_TOKEN}")
  cookies="$(cat /tmp/cookies| awk '/SESSION/ { print $NF }')"
  SPINNAKER_HEADER="Cookie: SESSION=$cookies"
  PIPELINE_ID="5fb496f3-1e92-4974-a09c-2d64d99ae1e5"
  flag=false
  reviews=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/pulls/${PRNumber}/reviews?per_page=100"| jq --raw-output '.[] | {state: .state} | @base64')
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
  # https://developer.github.com/v3/pulls/reviews/#list-reviews-on-a-pull-request
  spinnakerBody=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${PIPELINE_ID}&limit=50" \
  -H "${SPINNAKER_HEADER}" | jq '[.[] | {status: .status,id: .id,startTime: .startTime,buildTime: .buildTime,commitId: .trigger.parameters.api_commit_id,pr_number: .trigger.parameters.pr_number}]')
  echo "Temporary logs for testing 3"
  echo "${spinnakerBody}"
  pipelines=$(echo "$spinnakerBody" | jq --raw-output '.[] | {pr_number: .pr_number,id: .id,status: .status,commitId: .commitId}| @base64')
  echo "$pipelines"
  for p in $pipelines; do
    pipeline="$(echo "$p"|base64 -d)"
    pCommitId=$(echo "$pipeline" | jq --raw-output '.commitId')
    pStatus=$(echo "$pipeline" | jq --raw-output '.status')
    echo "$pCommitId"
    echo "$pStatus"
      if [ "$pCommitId" = "$commitId" ] && ([ "$pStatus" = "NOT_STARTED" ] || [ "$pStatus" = "RUNNING" ]); then
        echo "CommitId already in queue, ignoring for bvt execution"
        exit 0
      fi
  done
  for p in $pipelines; do
  pipeline="$(echo "$p"|base64 -d)"
  pId=$(echo "$pipeline" | jq --raw-output '.id')
  pPRNumber=$(echo "$pipeline" | jq --raw-output '.pr_number')
  pStatus=$(echo "$pipeline" | jq --raw-output '.status')
    if [ "$pPRNumber" = "$PRNumber" ] && ([ "$pStatus" = "NOT_STARTED" ] || [ "$pStatus" = "RUNNING" ]); then
      spinnakerCancelRequest=$(curl --location --request PUT "https://deploy-api.razorpay.com/pipelines/$pId/cancel" \
      -H "${SPINNAKER_HEADER}" )
      echo "$spinnakerCancelRequest"
    fi
  done
  echo "Triggering webhook for bvt testing execution for :" + "$commitId"
  curl -X POST \
  -u github-actions:"$SPINNAKER_PASSWORD" \
  https://deploy-github-actions.razorpay.com/webhooks/webhook/"$WEBHOOK_TRIGGER" \
  -H "content-type: application/json" \
  -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipRoast\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$PRNumber\",\"state\":\"approved\"} }"

}

run_bvt_suite_when_approved
