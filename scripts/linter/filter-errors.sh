#!/bin/bash

# Read the contents of errors_report.json into the variable 'eslint_errors'
eslint_errors=$(cat errors_report.json)

# Initialize an empty array for the result
result=()

# Iterate over each error object using jq
for error in $(echo "${eslint_errors}" | jq -r '.[] | @base64'); do
    # Decode the error object
    decoded_error=$(echo "${error}" | base64 --decode)

    # Extract filePath, messages, and errorCount using jq
    filePath=$(echo "${decoded_error}" | jq -r '.filePath')
    messages=$(echo "${decoded_error}" | jq -r '.messages')

    # Filter messages based on the ruleId condition using jq
    newMessages=$(echo "$messages" | jq 'map(select((.ruleId // "") | contains("i18n-rule")))')

    # If there are filtered messages, create a new error object
    if [ -n "$(echo "${newMessages}" | jq '.')" ] && [ "$(echo "${newMessages}" | jq 'length')" -gt 0 ]; then
        errorCount="$(echo "${newMessages}" | jq 'length')"

        # Encode the filePath and create a new error object
        newError='{"filePath":"'"${filePath}"'","errorCount":'${errorCount}',"messages":'${newMessages}',"warningCount":0}'
        result+=("${newError}")

    fi

done

# Convert the result array to JSON
result_json='['$(
    IFS=,
    echo "${result[*]}"
)']'

# Write the result JSON to eslint_report.json
echo "${result_json}" >eslint_report.json
