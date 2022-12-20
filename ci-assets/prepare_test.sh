cp ./environment/.env.drone ./environment/.env.testing
touch /etc/php81/conf.d/assertion.ini /etc/php81/conf.d/memory.ini
echo "zend.assertions=1" >> /etc/php81/conf.d/assertion.ini
echo "assert.exception=1" >> /etc/php81/conf.d/assertion.ini
echo "memory_limit = 8192M" >> /etc/php81/conf.d/memory.ini
echo "opcache.interned_strings_buffer=32" >> /etc/php81/conf.d/memory.ini
apk add --no-cache wkhtmltopdf
php -m
chmod 777 -R storage
git config --global user.name $GIT_USERNAME
composer config --global github-oauth.github.com $GIT_TOKEN
composer config -g repos.packagist composer https://packagist.rzp.io
#composer global require hirak/prestissimo
composer install --no-interaction --optimize-autoloader
mkdir -p /opt/razorpay/certs/first_data
mkdir error_codes/
cd error_codes/
git init --quiet
git config core.sparseCheckout true
cp ../error_modules .git/info/sparse-checkout
git remote add origin https://$GIT_TOKEN@github.com/razorpay/error-mapping-module
git fetch origin master --quiet
git checkout origin/master --quiet
