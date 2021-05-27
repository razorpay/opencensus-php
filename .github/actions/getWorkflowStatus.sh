#!/bin/sh
get_workflow_status_value() {
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  branch_name=${BRANCH}
  workflow_file=${WORKFLOW}
  workflow_details=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/actions/workflows/$workflow_file/runs?branch=$branch_name&per_page=1" | jq --raw-output '{conclusion: .workflow_runs[0].conclusion, status: .workflow_runs[0].status}')
  workflow_status=$(echo "$workflow_details" | jq --raw-output '.status')
  while [ "$workflow_status" != "completed" ]
  do
    sleep 3m
    workflow_details=$(curl -sSL -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/actions/workflows/$workflow_file/runs?branch=$branch_name&per_page=1" | jq --raw-output '{conclusion: .workflow_runs[0].conclusion, status: .workflow_runs[0].status}')
    workflow_status=$(echo "$workflow_details" | jq --raw-output '.status')
  done
  workflow_conclusion=$(echo "$workflow_details" | jq --raw-output '.conclusion')
}

get_workflow_status_value
echo $workflow_conclusion
