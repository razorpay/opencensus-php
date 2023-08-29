#!/bin/bash

function run_tests {
  echo "--------------------"
  echo "Fixture ENV vars"
  echo "$RUN_FIXTURES" "$RUN_FIXTURES_ONCE"
  echo "--------------------"

  XDEBUG_MODE=coverage php vendor/phpunit/phpunit/phpunit -d pcov.enabled=1 -d max_execution_time=120 -d memory_limit=-1 -d pcov.directory=/app --testsuite "$TEST_SUITE_NAME" --debug --verbose  --coverage-clover clover.xml
  # Clover name is imported from deploy.yml job step
  dest_file=clover_cov_"$CLOVER_NAME".xml
  echo "Destination file- " "$dest_file"
  mkdir "$GITHUB_WORKSPACE"/clover_files
  cp clover.xml "$GITHUB_WORKSPACE"/clover_files/"$dest_file"
}

run_tests
#Echo the dir structure to make sure that files are getting generated
echo "-------------"
echo "New clover folder structure-"
echo "-------------"
ls "$GITHUB_WORKSPACE"/clover_files
exit $?
