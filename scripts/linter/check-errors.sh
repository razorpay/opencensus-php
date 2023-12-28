#!/bin/bash

# Specify the path to your JSON file
json_file="eslint_report.json"

# Check if the JSON file exists
if [ ! -f "$json_file" ]; then
    echo "JSON file '$json_file' does not exist."
    exit 0
fi

report_contents=$(cat eslint_report.json)

# Check if report_contents is non-empty
if [ -n "$report_contents" ]; then
    error_found=false

    # Iterate over the array and check if json contains any error
    for obj in $(echo "${report_contents}" | jq -r '.[] | @base64'); do
        _jq() {
            echo ${obj} | base64 --decode | jq -r ${1}
        }
        errorCount=$(echo $(_jq '.errorCount'))
        if [ "$errorCount" -gt 0 ]; then
            error_found=true
        fi
    done

    # Check if error was found and take further action if needed
    if [ "$error_found" = true ]; then
        echo "I18n errors detected."
        echo "hardcoding_found=true" >>$GITHUB_OUTPUT
    fi
else
    echo "No i18n errors found empty."
    echo "hardcoding_found=false" >>$GITHUB_OUTPUT
    exit 0
fi
