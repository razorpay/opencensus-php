#!/bin/sh

run_bvt_suite_when_approved() {
  PRNumber=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
  commitId=$(jq --raw-output .pull_request.head.sha "$GITHUB_EVENT_PATH")
  skipRoast="false"
  if [ "${HOTFIX}" = "true" ] || [ "${REVERT}" = "true" ] || [ "${ONLY_IGNORE_FILES}" = "true" ]; then
    skipRoast="true"
  fi
  roastPRCommit=${ROAST_PR_COMMIT}
  statusCode=$(curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' \
    --header "Authorization: Bearer ${GIT_TOKEN}")
  cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
  SPINNAKER_HEADER="Cookie: SESSION=$cookies"
  # pipeline id - Trigger BVT Execution -> New pipeline to trigger the BVT execution in parallel (Maintaining two child pipelines now)
  PIPELINE_ID="842e0854-3a08-4e67-9881-a9ea1d005b31"
  api_instance=${API_INSTANCE}
  # https://developer.github.com/v3/pulls/reviews/#list-reviews-on-a-pull-request
  echo "Status Code for fetching spinnaker cookie $statusCode"
  if [ -z "$roastPRCommit"]; then
    roastPRCommit="latest"
  fi
  if [ "$skipRoast" = "true" ]; then
    if [ "$statusCode" = 200 ]; then
      spinnakerBody=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=${PIPELINE_ID}&limit=50" \
        -H "${SPINNAKER_HEADER}" | jq '[.[] | {status: .status,id: .id,startTime: .startTime,buildTime: .buildTime,commitId: .stages[0].outputs.app_commit_id,pr_number: .stages[0].outputs.pr_number,apiInstance: .trigger.parameters.instance}]')
      pipelines=$(echo "$spinnakerBody" | jq --raw-output '.[] | {pr_number: .pr_number,id: .id,status: .status,commitId: .commitId,apiInstance: .apiInstance}| @base64')
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
        api_instance=$(echo "$pipeline" | jq --raw-output '.apiInstance')
        if [ "$spinnakerCancelRequestStatusCode" = 200 ]; then
          echo "Pipeline cancellation succeeded"
        else
          echo "Pipeline cancellation failed"
        fi
      fi
    done
    fi
    echo "Triggering skip regression webhook for bvt execution for :" + "$commitId"
    curl -X POST \
      -u github-actions:"$SPINNAKER_PASSWORD" \
      https://deploy-github-actions.razorpay.com/webhooks/webhook/"$WEBHOOK_TRIGGER" \
      -H "content-type: application/json" \
      -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipRoast\", \"roast_commit_id\":\"$roastPRCommit\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$PRNumber\",\"state\":\"approved\"},\"instance\":\"$api_instance\" }"
    exit 0
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
  echo "Triggering webhook for bvt testing execution for :" + "$commitId"
  echo "Triggering webhook for bvt testing execution for Roast PR :" + "$roastPRCommit"
  curl -X POST \
    -u github-actions:"$SPINNAKER_PASSWORD" \
    https://deploy-github-actions.razorpay.com/webhooks/webhook/"$WEBHOOK_TRIGGER" \
    -H "content-type: application/json" \
    -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipRoast\", \"roast_commit_id\":\"$roastPRCommit\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$PRNumber\",\"state\":\"approved\"},\"instance\":\"$api_instance\" }"
}

run_bvt_suite_when_approved
