#!/bin/sh
commitId=${COMMIT_ID}
URI="https://api.github.com"
API_HEADER="Accept: application/vnd.github.v3+json"
AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
branch_name=${BRANCH}
branch_name=$(echo "$branch_name"|jq -sRr @uri)
workflow_file=${WORKFLOW}
iterate_count=0

while [ $iterate_count -lt 15 ] ;
do
  workflow_response=$(curl -sSD build_image_workflow_response_header.txt -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/repos/${GITHUB_REPOSITORY}/actions/workflows/$workflow_file/runs?branch=$branch_name&per_page=5" )
  echo "$workflow_response" > build_image_workflow_response_body.txt
  cat build_image_workflow_response_header.txt >> build_image_workflow_log.txt
  cat build_image_workflow_response_body.txt >> build_image_workflow_log.txt
  #escape charaters that can cause jq to fail
  fixed_response=$(echo $workflow_response | tr -d '\n' | tr -d '\r' | tr -d '\t')
  #Iterate over the values obtained from the workflow runs as there can be multiple commits of the same branch running workflows
  for row in $(echo "$fixed_response" | jq -r '.workflow_runs[] | @base64');
  do
    workflow_run=$(echo "$row" | base64 --decode | jq -r '{conclusion: .conclusion, status: .status , commit: .head_sha}')
    workflow_conclusion=$(echo "$workflow_run"| jq -r ".conclusion")
    workflow_status=$(echo "$workflow_run"| jq -r ".status")
    workflow_commit=$(echo "$workflow_run"| jq -r ".commit")
#    echo "running for workflow_commit $workflow_commit $workflow_status"
    #exit condition if the commit matches and the status is not in progress or pending
    if [ "$workflow_commit" = "$commitId" ] && ([ "$workflow_status" = "completed" ] || [ "$workflow_status" = "cancelled" ] ||  [ "$workflow_status" = "failure" ] ); then
      echo $workflow_conclusion
      exit 0
    fi
  done
  iterate_count=$((iterate_count + 1))
  sleep 180
done
#defaulting the workflow conclusion to cancelled as either the build images job was not picked up for the commit or is taking lot of time to build the images
echo "cancelled"
