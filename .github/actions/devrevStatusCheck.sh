#!/bin/bash

run_devrev_status_check()
{
  if [ "${HOTFIX_LABEL}" = "true" ]
  then
    echo "Hotfix branch detected - skipping DevRev check"
    exit 0
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

    # Extract and check ticket subtype
    devrev_issue_subtype=$(echo "${devrev_response}" | jq -r '.work.subtype' | awk '{print tolower($0)}')
    
    if [ "${devrev_issue_subtype}" != "afd" ]
    then
      echo "DevRev issue ${DEVREV_ISSUE_ID} has invalid subtype: ${devrev_issue_subtype}. Only AFD subtype is allowed."
      exit 1
    fi
    echo "DevRev issue ${DEVREV_ISSUE_ID} has valid AFD subtype"
    devrev_issue_status=$(echo "${devrev_response}" | jq .work.stage.name)
    devrev_issue_status_in_lower_case="$(echo "$devrev_issue_status" | awk '{print tolower($0)}')"
    
    if [ "$devrev_issue_status_in_lower_case" != "\"approved\"" ]
    then
      echo "DevRev issue ${DEVREV_ISSUE_ID} is not approved. Please ensure the issue is approved and re-trigger the check."
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

run_devrev_status_check