#!/bin/bash
run_jira_status_check()
{
if [ "${HOTFIX_LABEL}" = "true" ]
then
  echo "This is a hotfix branch. Jira check is not needed here."
  exit 0
fi

if [ "${BUGFIX_LABEL}" = "true" ]
then
  echo "This branch contains bug fix. Jira check is not needed here."
  exit 0
fi

if [ "${PAYMENTS_BU_FLAG}" = "true" ]
then
  if [ "${JIRA_ISSUE_ID}" = "" ]
  then
    echo "Jira Id is not present for PR."
    exit 1
  else
    jira_issue_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" --user "${USER_EMAIL}":"${API_TOKEN}" | jq .fields.status.name)
    echo "Server response is <$jira_issue_status>"
    jira_issue_status_in_lower_case="$(echo "$jira_issue_status" | awk '{print tolower($0)}')"
    if [ "$jira_issue_status_in_lower_case" == "\"approved\"" ]
    then
      echo "Jira issue is approved."
      exit 0
    else
      echo "Jira issue is not approved."
      exit 1
    fi
  fi
else
  echo "PR is not for Payments BU. Jira check is not needed here."
  exit 0
fi
}
run_jira_status_check
