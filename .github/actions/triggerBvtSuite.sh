#!/bin/sh

run_bvt_suite_when_approved() {
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  action=$(jq --raw-output .action "$GITHUB_EVENT_PATH")
  state=$(jq --raw-output .review.state "$GITHUB_EVENT_PATH")
  number=$(jq --raw-output .pull_request.number "$GITHUB_EVENT_PATH")
  commitId=$(jq --raw-output .pull_request.head.sha "$GITHUB_EVENT_PATH")
  skipRoast=${SKIP_ROAST} 
  # https://developer.github.com/v3/pulls/reviews/#list-reviews-on-a-pull-request 
  body=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/pulls/${number}/reviews?per_page=100")
  reviews=$(echo "$body" | jq --raw-output '.[] | {state: .state} | @base64')
  for r in $reviews; do
    review="$(echo "$r" | base64 -d)"
    rState=$(echo "$review" | jq --raw-output '.state')
    if ([ "$rState" = "APPROVED" ] && [ "$skipRoast" = "false" ]); then
      echo "Triggering webhook for bvt execution for :" $commitId
      curl -X POST \
      -u github-actions:$SPINNAKER_PASSWORD \
       https://deploy-github-actions.razorpay.com/webhooks/webhook/$WEBHOOK_TRIGGER \
       -H "content-type: application/json" \
       -d "{\"review\":{\"state\":\"approved\", \"skip_roast\":\"$skipRoast\"},\"pull_request\":{\"head\":{ \"sha\":\"$commitId\"},\"number\":\"$number\",\"state\":\"approved\"} }"
      break
    else
      echo "PR is not approved state or skipRoast is enabled, ignoring for bvt execution"  
    fi
  done
}

run_bvt_suite_when_approved
