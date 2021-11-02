# alpine:3.7
# -> razorpay/ongii:php7.3 ->
FROM c.rzp.io/razorpay/onggi:php-7.3-nginx
# -> razorpay/dashboard:{GIT_COMMIT_HASH}

COPY composer.json composer.lock /app/

# Copy the composer auth file (with GIT_TOKEN)
COPY composer-auth.json /root/.composer/auth.json

WORKDIR /app

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts \
    && rm -rf /root/.composer

# This is the final production image
# Define these late so as to improve docker caching
ARG GIT_COMMIT_HASH

RUN mkdir -p public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt

COPY --chown=nginx:nginx . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

STOPSIGNAL SIGQUIT

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
