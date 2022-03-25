#!/bin/bash
log_file_name="test-output.log"
export START_TIME=$(date +%s)
echo "start-time: ${START_TIME}"
php vendor/phpunit/phpunit/phpunit -d memory_limit=2048M --testsuite "Feature-6 Test Suite" --printer="Codedungeon\PHPUnitPrettyResultPrinter\Printer" >> $log_file_name
export END_TIME=$(date +%s)
echo "end-time: ${END_TIME}"
cat $log_file_name
echo "${START_TIME},${END_TIME}" >> utMetrics.csv

