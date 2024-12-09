#!/bin/bash
run_jira_status_check()
{
if [ "${JIRA_ISSUE_ID}" = "" ]
then
  echo "Jira Id is not present for PR."
  exit 1
elif [ "${BASE_URL}" = "" ]
then
  echo "Jira BASE_URL is not present for PR."
  exit 1
elif [ "${API_TOKEN}" = "" ]
then
  echo "Jira API_TOKEN is not present for PR."
  exit 1
elif [ "${USER_EMAIL}" = "" ]
then
  echo "USER_EMAIL is not present for PR."
  exit 1
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
    exit 1
  fi
fi
}
run_jira_status_check
