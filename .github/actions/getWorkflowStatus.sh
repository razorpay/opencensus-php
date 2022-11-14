#!/bin/sh
get_workflow_status_value() {
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  branch_name=${BRANCH}
  workflow_file=${WORKFLOW}
  workflow_response=$(curl -sSD build_image_workflow_response_header.txt -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/actions/workflows/$workflow_file/runs?branch=$branch_name&per_page=1" | jq '.')

  echo "$workflow_response" > build_image_workflow_response_body.txt
  cat build_image_workflow_response_header.txt >> build_image_workflow_log.txt
  cat build_image_workflow_response_body.txt >> build_image_workflow_log.txt

  workflow_details=$(echo "$workflow_response" | jq --raw-output '{conclusion: .workflow_runs[0].conclusion, status: .workflow_runs[0].status}')
  workflow_status=$(echo "$workflow_details" | jq --raw-output '.status')
  while [ "$workflow_status" != "completed" ]
  do
    sleep 3m
    workflow_response=$(curl -sSD build_image_workflow_response_header.txt -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/actions/workflows/$workflow_file/runs?branch=$branch_name&per_page=1" | jq '.')

    echo "$workflow_response" > build_image_workflow_response_body.txt
    cat build_image_workflow_response_header.txt >> build_image_workflow_log.txt
    cat build_image_workflow_response_body.txt >> build_image_workflow_log.txt

    workflow_details=$(echo "$workflow_response" | jq --raw-output '{conclusion: .workflow_runs[0].conclusion, status: .workflow_runs[0].status}')
    workflow_status=$(echo "$workflow_details" | jq --raw-output '.status')
  done
  workflow_conclusion=$(echo "$workflow_details" | jq --raw-output '.conclusion')
}

get_workflow_status_value
echo $workflow_conclusion
