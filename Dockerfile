# alpine:3.7
# -> razorpay/pithos:rzp-alpine-base-alohomora
# -> razorpay/pithos:rzp-php7.1 ->
FROM razorpay/pithos:rzp-php7.1-nginx
# -> razorpay/dashboard:{GIT_COMMIT_HASH}

# This is the final production image
ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

COPY composer.json composer.lock /app/

WORKDIR /app

# A single character change in this command will trigger a new
# composer install
RUN composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache

RUN mkdir -p public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt

COPY --chown=nginx:nginx . /app/
# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

EXPOSE 80
ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
