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

if [ "${USER_CHECK}" = "success" ]
then
  if [ "${NON_MIGRATION_FLAG}" = "true" ]
  then
    if [ "${JIRA_ISSUE_ID}" = "" ]
    then
      echo "Jira Id is not present for Non-Migration PR."
      exit 1
    else
      jira_issue_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" --user "${USER_EMAIL}":"${API_TOKEN}" | jq .fields.status.name)
      echo "Server response is <$jira_issue_status>"
      if [ $jira_issue_status == "\"Approved\"" ]
      then
        echo "Jira issue is approved."
        exit 0
      else
        echo "Jira issue is not approved."
        exit 1
      fi
    fi
  else
    echo "This is a migration PR. Jira check is not needed here."
    exit 0
  fi
elif [ "${USER_CHECK}" = "fail" ]
then
  echo "Github user is not a member of Payments BU"
  exit 0
else
  echo "Unable to fetch Github user. Please re-run the job again"
  exit 1
fi
}
run_jira_status_check
