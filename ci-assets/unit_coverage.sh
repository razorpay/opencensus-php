#!/bin/bash

function run_tests {
  XDEBUG_MODE=coverage php vendor/phpunit/phpunit/phpunit -d memory_limit=4096M -d pcov.directory=/app --testsuite "$TEST_SUITE_NAME" --debug --verbose  --coverage-clover clover.xml
}

function push_to_sonar {
  file="clover.xml"
  if [ -f "$file" ]; then
    dir=$(pwd)
    echo "--------Downloading dependencies----------"
    wget https://github.com/sgerrand/alpine-pkg-glibc/releases/download/2.23-r3/glibc-2.23-r3.apk
    apk --allow-untrusted --force add glibc-2.23-r3.apk
    apk update && apk add nodejs && apk del gnu-libiconv
    apk add --no-cache \
      ca-certificates \
      curl \
      unzip \
      libc6-compat \
      openjdk11
    wget https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-4.7.0.2747-linux.zip
    unzip sonar-scanner-cli-4.7.0.2747-linux.zip
    rm sonar-scanner-cli-4.7.0.2747-linux.zip
    export PATH="$(pwd)/sonar-scanner-4.7.0.2747-linux/bin:$PATH"
    ln -s /sonar-scanner-4.7.0.2747-linux/bin/sonar-scanner /bin/sonar-scanner
    sed -i 's/use_embedded_jre=true/use_embedded_jre=false/g' sonar-scanner-4.7.0.2747-linux/bin/sonar-scanner
    echo "-------------Pushing report to sonar--------"
    sonar-scanner -X \
      -Dsonar.host.url="$SONAR_HOST" \
      -Dsonar.projectKey="$SONAR_PROJECT_ID" \
      -Dsonar.projectName="$SONAR_PROJECT_ID" \
      -Dsonar.projectVersion="$GIT_COMMIT_HASH" \
      -Dsonar.login="$SONAR_TOKEN" \
      -Dsonar.sources="$SOURCE_DIR" \
      -Dsonar.exclusions="$EXCLUDE_FILES" \
      -Dsonar.php.coverage.reportPaths="$dir"/"$file"
  fi
}

run_tests
push_to_sonar
exit $?
