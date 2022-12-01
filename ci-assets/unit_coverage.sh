#version
phpunit=7.3.0
phpcodecoverage=6.1.4

function init_setup
{
    pecl install xdebug-2.9.0 && echo 'zend_extension=/usr/lib/php81/modules/xdebug.so' >> /etc/php81/php.ini
    mkdir -p /opt/razorpay/certs/first_data
    touch /etc/php81/conf.d/assertion.ini
    echo "zend.assertions=1" >> /etc/php81/conf.d/assertion.ini
    echo "assert.exception=1" >> /etc/php81/conf.d/assertion.ini
    sed -i 's/max_execution_time.*/max_execution_time=120/' /etc/php81/php.ini
    sed -i 's/memory_limit.*/memory_limit=-1/' /etc/php81/php.ini
    composer config --global github-oauth.github.com $GIT_TOKEN
    composer global require hirak/prestissimo
    composer remove --dev orchestra/testbench phpunit/phpunit-mock-objects --no-interaction
    composer require --dev phpunit/phpunit:"$phpunit" phpunit/php-code-coverage:"$phpcodecoverage" symfony/phpunit-bridge  --update-with-dependencies
    composer require --dev pcov/clobber
    composer dump-autoload -og
}

function run_tests
{
    php vendor/phpunit/phpunit/phpunit -d memory_limit=4096M --testsuite "$TEST_SUITE_NAME"  --debug --verbose  --coverage-clover clover.xml
}

function push_to_sonar
{
    file="clover.xml"
    if [ -f "$file" ];then
        dir=$(pwd)
        echo "--------Downloading dependencies----------"
        wget https://github.com/sgerrand/alpine-pkg-glibc/releases/download/2.23-r3/glibc-2.23-r3.apk
        apk --allow-untrusted --force add glibc-2.23-r3.apk
        apk update && apk add nodejs && apk del gnu-libiconv
        wget https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-3.3.0.1492-linux.zip
        unzip sonar-scanner-cli-3.3.0.1492-linux.zip
        rm sonar-scanner-cli-3.3.0.1492-linux.zip
        PATH=$PATH:$(pwd)/sonar-scanner-3.3.0.1492-linux/bin
        export PATH
        echo "-------------Pushing report to sonar--------"
        sonar-scanner -X \
                -Dsonar.host.url="$SONAR_HOST" \
                -Dsonar.projectKey="$SONAR_PROJECT_ID" \
                -Dsonar.projectName="$SONAR_PROJECT_ID" \
                -Dsonar.projectVersion="$GIT_COMMIT_ID" \
                -Dsonar.login="$SONAR_TOKEN" \
                -Dsonar.sources="$SOURCE_DIR" \
                -Dsonar.exclusions="$EXCLUDE_FILES" \
                -Dsonar.php.coverage.reportPaths="$dir"/"$file"
    fi
}

init_setup
run_tests
push_to_sonar
exit $?
