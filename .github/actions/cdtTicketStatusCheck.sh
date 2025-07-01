#!/bin/bash
run_jira_status_check()
{
if [ "${JIRA_ISSUE_ID}" = "" ]
then
  echo "Jira Id is not present for PR."
elif [ "${BASE_URL}" = "" ]
then
  echo "Jira BASE_URL is not present for PR."
elif [ "${API_TOKEN}" = "" ]
then
  echo "Jira API_TOKEN is not present for PR."
elif [ "${USER_EMAIL}" = "" ]
then
  echo "USER_EMAIL is not present for PR."
else
  echo "Jira Id is ${JIRA_ISSUE_ID}"
  jira_issue_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" --user "${USER_EMAIL}":"${API_TOKEN}" | jq .fields.status.name)
  echo "Server response is <$jira_issue_status>"
  jira_issue_status_in_lower_case="$(echo "$jira_issue_status" | awk '{print tolower($0)}')"
  if [[ "$jira_issue_status_in_lower_case" == "\"done\""  || "$jira_issue_status_in_lower_case" == "\"approved\"" ]]
  then
    echo "Jira issue is approved."
    exit 0
  else
    echo "Jira issue is not approved. If your Jira issue is approved and the check is still failing, please add/remove a
    label (preferred approach if you don't want to re-trigger BVTs) or push a new commit to your PR."
  fi
fi

if [ -z "${DEVREV_ISSUE_ID}" ]
then
  echo "No DevRev ID found in PR"
  exit 1
else
  echo "Checking DevRev ID: ${DEVREV_ISSUE_ID}"

  # Check if the DEVREV issue is approved
  devrev_response=$(curl -s -X POST "https://api.devrev.ai/works.get" \
    -H "Authorization: Bearer ${DEVREV_API_TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{\"id\": \"${DEVREV_ISSUE_ID}\"}")

  devrev_issue_status=$(echo "${devrev_response}" | jq .work.stage.name)
  devrev_issue_subtype=$(echo "${devrev_response}" | jq .work.subtype)
  devrev_issue_status_in_lower_case="$(echo "$devrev_issue_status" | awk '{print tolower($0)}')"
  devrev_issue_subtype_in_lower_case="$(echo "$devrev_issue_subtype" | awk '{print tolower($0)}')"

  if [ "$devrev_issue_status_in_lower_case" != "\"approved\"" ] || [ "$devrev_issue_subtype_in_lower_case" != "\"cdt\"" ]
  then
    echo "DevRev issue ${DEVREV_ISSUE_ID} is not approved or not of subtype cdt. Please ensure the issue is approved and re-trigger the check. subtype ${devrev_issue_subtype_in_lower_case}, status ${devrev_issue_status_in_lower_case}"
    exit 1
  fi

  # Check for duplicate PRs with the DEVREV ID
  pr_search_response=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
  "https://api.github.com/search/issues?q=repo:razorpay/api+type:pr+\"${DEVREV_ISSUE_ID}\"+in:body+is:pr" \
  --write-out "%{http_code}" --output pr_search_response.json)

  http_status=$(tail -n1 <<< "$pr_search_response")

  # Rate limit handling
  if [ "$http_status" -eq 403 ]; then
    echo "GitHub API rate limit reached - please try again later"
    exit 1
  fi

  pr_count=$(jq -r '.total_count' pr_search_response.json)


  if [ "$pr_count" -gt 1 ]; then
    echo "DevRev ID ${DEVREV_ISSUE_ID} is already linked to other PR(s):"
    jq -r '.items[].html_url' pr_search_response.json
    exit 1
  fi

  echo "DevRev ID ${DEVREV_ISSUE_ID} is approved and not linked to other PRs"
  exit 0
fi
}
run_jira_status_check
