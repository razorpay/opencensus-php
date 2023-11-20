apk update
echo "adding xdebug"
apk add --update --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ php81-xdebug
echo 'zend_extension=xdebug.so' >> /etc/php81/php.ini
sed -i 's/max_execution_time.*/max_execution_time=120/' /etc/php81/php.ini
sed -i 's/memory_limit.*/memory_limit=-1/' /etc/php81/php.ini
touch /etc/php81/conf.d/assertion.ini
echo "zend.assertions=1" >> /etc/php81/conf.d/assertion.ini
echo "assert.exception=On" >> /etc/php81/conf.d/assertion.ini
composer config --global github-oauth.github.com $GIT_TOKEN
export COMPOSER_CACHE_DIR=".composer/cache"
mkdir -p $COMPOSER_CACHE_DIR
composer install --no-interaction --optimize-autoloader
cp ./environment/.env.defaults ./environment/.env.testing
APP_MODE=testing APP_ENV=testing php vendor/phpunit/phpunit/phpunit -d memory_limit=2048M --testsuite "Circuit Breaker Tests,User Session Tests,Unit Tests"
