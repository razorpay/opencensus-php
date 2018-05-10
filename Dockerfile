# alpine:3.7
# -> razorpay/pithos:rzp-alpine-base-alohomora
# -> razorpay/pithos:rzp-php7.1 ->
FROM razorpay/pithos:rzp-php7.1-nginx
# -> razorpay/dashboard:{GIT_COMMIT_HASH}

COPY composer.json composer.lock /app/

# Copy the composer auth file (with GIT_TOKEN)
COPY composer-auth.json /root/.composer/auth.json

WORKDIR /app

# A single character change in this command will trigger a new
# composer install
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts && rm -rf /root/.composer

# This is the final production image
# Define these late so as to improve docker caching
ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

RUN mkdir -p public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt

COPY --chown=nginx:nginx . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

EXPOSE 80
ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
