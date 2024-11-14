#!/bin/bash
run_jira_status_check()
{
if [ "${HOTFIX_LABEL}" = "true" ]
then
  echo "This is a hotfix branch. Jira check is not needed here."
  exit 0
fi

if [ "${JIRA_ISSUE_ID}" = "" ]
then
  echo "Jira Id is not present for PR."
  exit 1
else

  # Step 1: Check if the JIRA issue is approved
  jira_issue_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" --user "${USER_EMAIL}":"${API_TOKEN}" | jq .fields.status.name)
  echo "Server response for JIRA status is <$jira_issue_status>"
  jira_issue_status_in_lower_case="$(echo "$jira_issue_status" | awk '{print tolower($0)}')"
  if [ "$jira_issue_status_in_lower_case" != "\"approved\"" ]
  then
    echo "Jira issue is not approved. If your Jira issue is approved and the check is still failing, please add/remove a label or push a new commit to your PR to re-trigger the check."
    exit 1
  fi

  # Step 2: Use GitHub search API to find PRs with the JIRA ID in the body, across all open and closed PRs
  pr_search_response=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
  "https://api.github.com/search/issues?q=repo:razorpay/api+type:pr+${JIRA_ISSUE_ID}+in:body+is:pr" \
  --write-out "%{http_code}" --output pr_search_response.json)

  http_status=$(tail -n1 <<< "$pr_search_response")

  # Rate limit handling
  if [ "$http_status" -eq 403 ]; then
    echo "Rate limit reached, try again in some time."
    exit 1
  fi

  pr_count=$(jq -r '.total_count' pr_search_response.json)
  if [ "$pr_count" -gt 1 ]; then
    echo "JIRA ID ${JIRA_ISSUE_ID} is already linked to the following PR(s):"
    jq -r '.items[].html_url' pr_search_response.json
    echo "Ensure each PR has a unique JIRA ID."
    exit 1
  fi

  echo "Jira issue is approved and not associated with other PRs."
  exit 0

fi
}
run_jira_status_check
