#!/bin/bash

run_jira_status_check()
{
if [ "${HOTFIX_LABEL}" = "true" ]
then
  echo "Hotfix branch detected - skipping Jira check"
  exit 0
fi

if [ "${JIRA_ISSUE_ID}" = "" ]
then
  echo "No Jira ID found in PR"
  exit 1
else
  echo "Checking Jira ID: ${JIRA_ISSUE_ID}"
  
  # Check if the JIRA issue is approved
  jira_issue_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" --user "${USER_EMAIL}":"${API_TOKEN}" | jq .fields.status.name)
  jira_issue_status_in_lower_case="$(echo "$jira_issue_status" | awk '{print tolower($0)}')"
  
  if [ "$jira_issue_status_in_lower_case" != "\"approved\"" ]
  then
    echo "Jira issue ${JIRA_ISSUE_ID} is not approved. Please ensure the issue is approved and re-trigger the check."
    exit 1
  fi

  # Check for duplicate PRs with the JIRA ID
  pr_search_response=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
  "https://api.github.com/search/issues?q=repo:razorpay/api+type:pr+\"${JIRA_ISSUE_ID}\"+in:body+is:pr" \
  --write-out "%{http_code}" --output pr_search_response.json)

  http_status=$(tail -n1 <<< "$pr_search_response")

  # Rate limit handling
  if [ "$http_status" -eq 403 ]; then
    echo "GitHub API rate limit reached - please try again later"
    exit 1
  fi

  pr_count=$(jq -r '.total_count' pr_search_response.json)

  if [ "$pr_count" -gt 1 ]; then
    echo "Jira ID ${JIRA_ISSUE_ID} is already linked to other PR(s):"
    jq -r '.items[].html_url' pr_search_response.json
    exit 1
  fi

  echo "Jira ID ${JIRA_ISSUE_ID} is approved and not linked to other PRs"
  exit 0
fi
}

run_jira_status_check
