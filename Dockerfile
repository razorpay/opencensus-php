# alpine:3.6
# -> razorpay/pithos:rzp-alpine3.6-base-alohomora
# -> razorpay/pithos:rzp-alpine3.6-php7.1 ->
# -> razorpay/pithos:rzp-alpine3.6-php7.1-nginx
FROM razorpay/dashboard:ci
# -> razorpay/dashboard:{GIT_COMMIT_HASH}

# This is the final production image
ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

COPY --chown=nginx:nginx . /app/

WORKDIR /app

# A lot of cleanup here is useless because these things are already committed
# on the base layer
RUN composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-dev --no-interaction && \
    rm -rf /root/.composer && \
    # Generate /commit.txt
    echo ${GIT_COMMIT_HASH} > public/commit.txt

# Force overwrite for now
COPY dockerconf/www.conf /etc/php7/php-fpm.d

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
