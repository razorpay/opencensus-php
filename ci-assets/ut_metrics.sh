#!/bin/bash

apk add jq
apk add curl

log_file_name="test-output.log"

Metrics="$(cat $log_file_name | grep Tests: | tr -d .)"
metrics_file_name+="test-metrics.dat"
IFS=','
for i in $Metrics; do
  echo "$i" >> $metrics_file_name
done

START_TIME=$( cut -d "," -f 1 utMetrics.csv)
END_TIME=$( cut -d "," -f 2 utMetrics.csv)
TEST_SUITE_STATUS=$( cut -d "," -f 3 utMetrics.csv)
declare -i TOTAL=$(cat $metrics_file_name | grep Tests: | awk '{s+=$2} END {print s}')
declare -i SKIPPED=$(cat $metrics_file_name | grep Skipped: | awk '{s+=$2} END {print s}')
declare -i ERROR=$(cat $metrics_file_name | grep Errors: | awk '{s+=$2} END {print s}')
declare -i FAILURE=$(cat $metrics_file_name | grep Failures: | awk '{s+=$2} END {print s}')

FAILED=$(expr $ERROR + $FAILURE)
PASSED=$(expr $TOTAL - $SKIPPED)
PASSED=$(expr $PASSED - $FAILED)

echo "Start Time - ${START_TIME}"
echo "End Time - ${END_TIME}"
echo "Total - ${TOTAL}"
echo "Skipped - ${SKIPPED}"
echo "Failed - ${FAILED}"
echo  "Passed - ${PASSED}"

code_coverage=0

echo "INSERT INTO UTCoverage VALUES (\"API\",\"${GIT_COMMIT}\",\"${BRANCH}\",\"Payments\",\"{\\\"tags\\\": [\\\"PPG\\\", \\\"Methods\\\"], \\\"test_suite_name\\\": \\\"${TEST_SUITE_NAME}\\\"}\",\"${code_coverage}\",\"${START_TIME}\",\"${END_TIME}\",\"${PASSED}\",\"${FAILED}\",\"${SKIPPED}\");"

curl -X POST \
       https://mock-go.qa.razorpay.in/insert_qa_iteration \
       -H "content-type: text/plain" \
       -d "INSERT INTO UTCoverage VALUES (\"API\",\"${GIT_COMMIT}\",\"${BRANCH}\",\"Payments\",\"{\\\"tags\\\": [\\\"PPG\\\", \\\"Methods\\\"], \\\"test_suite_name\\\": \\\"${TEST_SUITE_NAME}\\\"}\",\"${code_coverage}\",\"${START_TIME}\",\"${END_TIME}\",\"${PASSED}\",\"${FAILED}\",\"${SKIPPED}\");"

failed=$(jq '.defects | with_entries(select(.value == 4 or .value == 3))  | keys' .phpunit.result.cache | tr -d '\n\t')
skipped=$(jq ' .defects | with_entries(select(.value == 1))  | keys' .phpunit.result.cache | tr -d '\n\t')

failed=$(sed 's/\\\\/\\\\\\\\/g' <<< "$failed")
skipped=$(sed 's/\\\\/\\\\\\\\/g' <<< "$skipped")

failed=$(sed 's/"/\\"/g' <<< "$failed")
skipped=$(sed 's/"/\\"/g' <<< "$skipped")

echo "$failed"
echo "$skipped"

echo "INSERT INTO ut_summary VALUES (\"${GIT_COMMIT}\",\"${TEST_SUITE_NAME}\",\"{\\\"failed\\\": "$failed", \\\"skipped\\\": "$skipped"}\", CURRENT_TIMESTAMP());"

curl --location --request POST 'https://mock-go.qa.razorpay.in/insert_qa_iteration' \
--header 'Content-Type: text/plain' \
--data-raw "INSERT INTO ut_summary VALUES (\"${GIT_COMMIT}\",\"${TEST_SUITE_NAME}\",\"{\\\"failed\\\": $failed, \\\"skipped\\\": $skipped}\", CURRENT_TIMESTAMP());"
#echo "Test Suite Status Code - ${TEST_SUITE_STATUS}"
#if [[ $TEST_SUITE_STATUS -eq 0 ]]
#then
#  echo "${TEST_SUITE_NAME} passed!"
#  exit 0
#fi
#echo "${TEST_SUITE_NAME} has failed. Please Check previous workflow step for more details"
#exit 1
