#!/bin/bash

# Read the contents of eslint_report.json into the variable 'i18n_errors'
i18n_errors=$(cat eslint_report.json)

# Parse JSON data and encode to base64
totalFoundErrors=0
noRegionSpecificKeywordErrors=0
noHrefHardcodingErrors=0
noCurrencyHardcodingErrors=0

# Iterate through each entry in the JSON array, encode to base64
for encoded_entry in $(echo "${i18n_errors}" | jq -r '.[] | @base64'); do
    # Decode the base64 entry
    entry=$(echo "${encoded_entry}" | base64 --decode)

    # Extract errorCount and messages array
    errorCount=$(echo "${entry}" | jq '.errorCount')
    messages=$(echo "${entry}" | jq -r '.messages')

    # Increment totalFoundErrors
    totalFoundErrors=$((totalFoundErrors + errorCount))

    # Iterate through messages array
    for encoded_message in $(echo "${messages}" | jq -r '.[] | @base64'); do
        # Decode the base64 message
        message=$(echo "${encoded_message}" | base64 --decode)

        # Extract ruleId
        ruleId=$(echo "${message}" | jq -r '.ruleId')

        # Increment counters based on ruleId
        case "${ruleId}" in
        "i18n-rules/no-region-specific-keyword")
            noRegionSpecificKeywordErrors=$((noRegionSpecificKeywordErrors + 1))
            ;;
        "i18n-rules/no-href-hardcoding")
            noHrefHardcodingErrors=$((noHrefHardcodingErrors + 1))
            ;;
        "i18n-rules/no-currency-hardcoding")
            noCurrencyHardcodingErrors=$((noCurrencyHardcodingErrors + 1))
            ;;
        esac
    done
done

# Output the result
output_result="{\"totalFoundErrors\": ${totalFoundErrors},\"noRegionSpecificKeywordErrors\": ${noRegionSpecificKeywordErrors},\"noHrefHardcodingErrors\": ${noHrefHardcodingErrors},\"noCurrencyHardcodingErrors\": ${noCurrencyHardcodingErrors}}"

echo "$output_result"
# Store the output in a GitHub Actions output variable
echo "i18n_errors_metrics=${output_result}" >>$GITHUB_OUTPUT