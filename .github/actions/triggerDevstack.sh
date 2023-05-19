#!/bin/sh

run_devstack() {
  PRNumber=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
  commitId=${COMMIT_ID}
  skipDevstack=${SKIP_DEVSTACK}
  roastPRCommit=${ROAST_PR_COMMIT}
  WEBHOOK_TRIGGER=${WEBHOOK_TRIGGER}
  PIPELINE_ID="51ab409e-1ce1-4c59-ae13-f702c02a9c4a"

  statusCode=$(curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' \
    --header "Authorization: Bearer ${GIT_TOKEN}")
  cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
  SPINNAKER_HEADER="Cookie: SESSION=$cookies"
  echo "Status Code for fetching spinnaker cookie $statusCode"

  if [ -z "$roastPRCommit"]; then
    roastPRCommit="latest"
  fi

  if [ "$statusCode" = 200 ]; then
    spinnakerBody=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${PIPELINE_ID}&limit=100" \
      -H "${SPINNAKER_HEADER}" | jq '[.[] | {status: .status,id: .id,startTime: .startTime,buildTime: .buildTime,commitId: .stages[0].outputs.app_commit_id,pr_number: .stages[0].outputs.pr_number,apiInstance: .trigger.parameters.instance,pRoastCommitId: .trigger.payload.review.roast_commit_id}]')
    pipelines=$(echo "$spinnakerBody" | jq --raw-output '.[] | {pr_number: .pr_number,id: .id,status: .status,commitId: .commitId,apiInstance: .apiInstance,pRoastCommitId: .pRoastCommitId}| @base64')
    for p in $pipelines; do
      pipeline="$(echo "$p" | base64 -d)"
      pCommitId=$(echo "$pipeline" | jq --raw-output '.commitId')
      pStatus=$(echo "$pipeline" | jq --raw-output '.status')
      pRoastCommitId=$(echo "$pipeline" | jq --raw-output '.pRoastCommitId')
      if [ "$pCommitId" = "$commitId" ] && [ "$roastPRCommit" = "$pRoastCommitId" ] && ([ "$pStatus" = "RUNNING" ] || [ "$pStatus" = "SUCCEEDED" ]); then
        echo "$pCommitId"
        echo "$pStatus"
        echo "CommitId already in queue or SUCCEEDED in pipeline for same roast commit ID, ignoring for bvt execution"
        exit 0
      fi
    done
    for p in $pipelines; do
      pipeline="$(echo "$p" | base64 -d)"
      pId=$(echo "$pipeline" | jq --raw-output '.id')
      pCommitId=$(echo "$pipeline" | jq --raw-output '.commitId')
      pPRNumber=$(echo "$pipeline" | jq --raw-output '.pr_number')
      pStatus=$(echo "$pipeline" | jq --raw-output '.status')
      if [ "$pPRNumber" = "$PRNumber" ] && [ "$pStatus" = "RUNNING" ]; then
        spinnakerCancelRequestStatusCode=$(curl -o -s -w "%{http_code}" --location --request PUT "https://deploy-api.razorpay.com/pipelines/$pId/cancel" \
          -H "${SPINNAKER_HEADER}")
        echo "PR number $pPRNumber and Commit id $pCommitId in $pStatus state, this is being cancelled"
        #pipeline cancel logic
        #api_instance=$(echo "$pipeline" | jq --raw-output '.apiInstance')
        if [ "$spinnakerCancelRequestStatusCode" = 200 ]; then
          echo "Pipeline cancellation succeeded"
        else
          echo "Pipeline cancellation failed"
        fi
      fi
    done
  fi
  echo "Triggering webhook for BVT testing execution for :" + "$commitId"
  echo "Triggering webhook for BVT testing execution for Roast PR :" + "$roastPRCommit"
  curl -X POST \
    -u github-actions:"$SPINNAKER_PASSWORD" \
    https://deploy-github-actions.razorpay.com/webhooks/webhook/"$WEBHOOK_TRIGGER" \
    -H "content-type: application/json" \
    -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipDevstack\", \"roast_commit_id\":\"$roastPRCommit\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$PRNumber\",\"state\":\"approved\"}}"
}

run_devstack
