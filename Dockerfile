FROM c.rzp.io/razorpay/onggi-multi-arch:rzp-golden-image-nginx-php-8.1-fpm as opencensus-ext

WORKDIR /
ARG OPENCENSUS_VERSION_TAG=v0.8.0-beta
RUN set -eux && \
    wget -O - https://github.com/razorpay/opencensus-php/tarball/"${OPENCENSUS_VERSION_TAG}" | tar zx --strip=1
RUN cd /ext && phpize81 && ./configure --enable-opencensus --with-php-config=/usr/bin/php-config81 && make install

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts \
    && rm -rf /root/.composer

COPY composer.json composer.lock /app/

# Copy the composer auth file (with GIT_TOKEN)
COPY composer-auth.json /root/.composer/auth.json

WORKDIR /app
# This is the final production image
# Define these late so as to improve docker caching
ARG GIT_COMMIT_HASH

RUN pear81 config-set php_ini /etc/php81/php.ini && \
    pecl81 install opencensus-alpha && \
    mkdir -p public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt

COPY --chown=nginx:nginx . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

RUN echo "* Installing dependencies" && \
    apk update && \
    apk add --no-cache \
    python3 \
    py3-pip \
    gcc && \
    pip install razorpay.alohomora && \
    rm -rf ~/.cache/pip/* && \
    # Verify alohomora as running
    alohomora --help

STOPSIGNAL SIGQUIT

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
