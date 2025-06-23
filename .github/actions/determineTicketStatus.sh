#!/bin/bash

determine_ticket_status()
{
  echo "Starting final status determination..."

  # Get Jira and DevRev IDs from validate_tickets outputs
  JIRA_ID="${JIRA_ID}"
  DEVREV_ID="${DEVREV_ID}"

  echo "=== Environment Variables ==="
  echo "Jira ID: ${JIRA_ID}"
  echo "DevRev ID: ${DEVREV_ID}"
  echo "Hotfix Label: ${HOTFIX_LABEL}"
  echo "=========================="

  # Initialize status variables
  JIRA_RESULT="Failed"
  DEVREV_RESULT="Failed"
  FINAL_STATUS="failure"
  FINAL_MESSAGE="PR validation failed"
  SUMMARY=()

  # Handle hotfix case
  if [ "${HOTFIX_LABEL}" = "true" ]; then
    echo "Hotfix branch detected - skipping all checks"
    FINAL_STATUS="success"
    FINAL_MESSAGE="Hotfix branch - checks skipped"
    echo "Final status: Success - Hotfix branch"
    exit 0
  fi

  # Check Jira status if ID exists
  if [ -n "${JIRA_ID}" ]; then
    echo "Running Jira status check..."
    JIRA_ISSUE_ID="${JIRA_ID}" \
    BASE_URL="${JIRA_BASE_URL}" \
    USER_EMAIL="${JIRA_USER_EMAIL}" \
    API_TOKEN="${JIRA_API_TOKEN}" \
    GITHUB_TOKEN="${GITHUB_TOKEN}" \
    bash ./.github/actions/jiraStatusCheck.sh
    JIRA_EXIT_CODE=$?
    
    if [ $JIRA_EXIT_CODE -eq 0 ]; then
      JIRA_RESULT="Passed"
      SUMMARY+=("Jira Check: Passed")
    else
      SUMMARY+=("Jira Check: Failed")
    fi
    echo "Jira check exit code: ${JIRA_EXIT_CODE}"
  else
    echo "No Jira ID found"
  fi

  # Check DevRev status if ID exists
  if [ -n "${DEVREV_ID}" ]; then
    echo "Running DevRev status check..."
    DEVREV_ISSUE_ID="${DEVREV_ID}" \
    DEVREV_API_TOKEN="${DEVREV_API_TOKEN}" \
    GITHUB_TOKEN="${GITHUB_TOKEN}" \
    bash ./.github/actions/devrevStatusCheck.sh
    DEVREV_EXIT_CODE=$?
    
    if [ $DEVREV_EXIT_CODE -eq 0 ]; then
      DEVREV_RESULT="Passed"
      SUMMARY+=("DevRev Check: Passed")
    else
      SUMMARY+=("DevRev Check: Failed")
    fi
    echo "DevRev check exit code: ${DEVREV_EXIT_CODE}"
  else
    echo "No DevRev ID found"
  fi

  echo "=== Check Results ==="
  echo "Jira Result: ${JIRA_RESULT}"
  echo "DevRev Result: ${DEVREV_RESULT}"
  echo "==================="

  # Determine final status
  if [ -z "${JIRA_ID}" ] && [ -z "${DEVREV_ID}" ]; then
    FINAL_STATUS="failure"
    FINAL_MESSAGE="No ticket validation checks were performed"
    echo "Final status: Failure - No checks were performed"
    exit 1
  elif [ "$JIRA_RESULT" = "Passed" ] || [ "$DEVREV_RESULT" = "Passed" ]; then
    FINAL_STATUS="success"
    FINAL_MESSAGE="At least one ticket validation passed"
    echo "Final status: Success - At least one check passed"
    exit 0
  else
    FINAL_STATUS="failure"
    FINAL_MESSAGE="No ticket validation passed"
    echo "Final status: Failure - No checks passed"
    exit 1
  fi
}

determine_ticket_status 