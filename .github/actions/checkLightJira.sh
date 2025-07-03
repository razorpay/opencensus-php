#!/bin/bash

run_ticket_validation() {
  JIRA_CHECK_PASSED=false
  DEVREV_CHECK_PASSED=false

  echo "Starting ticket validation..."

  # Jira check
  if [ -n "${JIRA_ISSUE_ID}" ]; then
    echo "Checking Jira Ticket ID: ${JIRA_ISSUE_ID}"

    if [ -z "${BASE_URL}" ] || [ -z "${USER_EMAIL}" ] || [ -z "${API_TOKEN}" ]; then
      echo "Missing Jira credentials. Skipping Jira check."
    elif [[ "${JIRA_ISSUE_ID,,}" != lig-* ]]; then
      echo "Jira ID is not from LIGHT board. Skipping Jira check."
    else
      jira_status=$(curl -s "${BASE_URL}/rest/api/2/issue/${JIRA_ISSUE_ID}?fields=status" \
        --user "${USER_EMAIL}:${API_TOKEN}" | jq -r '.fields.status.name // empty')

      echo "Jira ticket status: $jira_status"

      if [[ "${jira_status,,}" == "done" || "${jira_status,,}" == "approved" ]]; then
        echo "Jira issue is approved."
        JIRA_CHECK_PASSED=true
      else
        echo "Jira issue is not in approved/done state."
      fi
    fi
  else
    echo "No Jira ID provided. Skipping Jira check."
  fi

  # DevRev check
  if [ -n "${DEVREV_ISSUE_ID}" ]; then
    echo "Checking DevRev Ticket ID: ${DEVREV_ISSUE_ID}"

    if [ -z "${DEVREV_API_TOKEN}" ]; then
      echo "Missing DevRev API token. Skipping DevRev check."
    else
      devrev_response=$(curl -s -X POST "https://api.devrev.ai/works.get" \
        -H "Authorization: Bearer ${DEVREV_API_TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{\"id\": \"${DEVREV_ISSUE_ID}\"}")

      devrev_status=$(echo "$devrev_response" | jq -r '.work.stage.name // empty')
      devrev_subtype=$(echo "$devrev_response" | jq -r '.work.subtype // empty')

      echo "DevRev ticket status: $devrev_status"
      echo "DevRev subtype: $devrev_subtype"

      if [[ "${devrev_status,,}" == "approved" && "${devrev_subtype,,}" == "light" ]]; then
        # Check for duplicate PRs
        pr_search_response=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
          "https://api.github.com/search/issues?q=repo:razorpay/api+type:pr+\"${DEVREV_ISSUE_ID}\"+in:body+is:pr" \
          --write-out "%{http_code}" --output pr_search_response.json)

        http_status=$(tail -n1 <<< "$pr_search_response")
        if [ "$http_status" -eq 403 ]; then
          echo "GitHub API rate limit hit. Skipping duplicate check."
        else
          pr_count=$(jq -r '.total_count' pr_search_response.json)
          if [ "$pr_count" -gt 1 ]; then
            echo "DevRev ID is already linked to another PR:"
            jq -r '.items[].html_url' pr_search_response.json
          else
            echo "DevRev issue is approved and unique."
            DEVREV_CHECK_PASSED=true
          fi
        fi
      else
        echo "DevRev issue is not approved or not of subtype 'light'."
      fi
    fi
  else
    echo "No DevRev ID provided. Skipping DevRev check."
  fi

  # Final result
  if [ "$JIRA_CHECK_PASSED" = true ] || [ "$DEVREV_CHECK_PASSED" = true ]; then
    echo "Ticket validation passed."
    exit 0
  else
    echo "Both Jira and DevRev checks failed"
    exit 1
  fi
}

run_ticket_validation
