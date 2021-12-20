# alpine:3.7
ARG ONGGI_IMAGE=c.rzp.io/razorpay/onggi:php-7.3-nginx
# -> razorpay/dashboard:{GIT_COMMIT_HASH}

FROM $ONGGI_IMAGE as opencensus-ext
WORKDIR /
ARG OPENCENSUS_VERSION_TAG=v0.7.6.4
RUN set -eux && \
    wget -O - https://github.com/razorpay/opencensus-php/tarball/"${OPENCENSUS_VERSION_TAG}" | tar zx --strip=1
RUN cd /ext && phpize && ./configure --enable-opencensus && make install

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts \
    && rm -rf /root/.composer

COPY composer.json composer.lock /app/

# Copy the composer auth file (with GIT_TOKEN)
COPY composer-auth.json /root/.composer/auth.json

WORKDIR /app
# This is the final production image
# Define these late so as to improve docker caching
ARG GIT_COMMIT_HASH

RUN pear config-set php_ini /etc/php7/php.ini && \
    pecl install opencensus-alpha && \
    mkdir -p public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt

COPY --chown=nginx:nginx . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

STOPSIGNAL SIGQUIT

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
