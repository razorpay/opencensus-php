#!/bin/bash

# Extracting labels from GitHub event payload using jq
# Assumes GitHub labels are in the form of an array of objects with a 'name' field
github_labels=$(jq --raw-output '.[] .name' <<< '${{ toJson(github.event.pull_request.labels) }}')

# Initializing a variable to track if 'ignore-i18n-linter' label is found
ignore_label_found=false

# Loop through each label retrieved from GitHub event payload
while IFS= read -r line; do
    # Check if the label is 'ignore-i18n-linter'
    if [ "$line" = "ignore-i18n-linter" ]; then
        ignore_label_found=true
        break  # If found, exit the loop
    fi
done <<< "$github_labels"  # Pass GitHub labels to the loop

# Check if the 'ignore-i18n-linter' label was found
if [ "$ignore_label_found" = true ]; then
    # Indicate that the 'ignore-i18n-linter' label was found
    echo "Linter ignore label found, exiting further process gracefully"
    echo "label_found=true" >> $GITHUB_OUTPUT  # Append to output for GitHub Actions
    exit 0  # Exit with success status
else
    # Indicate that the 'ignore-i18n-linter' label was not found
    echo "No linter ignore label found, linter will proceed now."
    echo "label_found=false" >> $GITHUB_OUTPUT  # Append to output for GitHub Actions
fi
