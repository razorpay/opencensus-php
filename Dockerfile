FROM razorpay/pithos:rzp-php7.0-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

WORKDIR /app

COPY composer.json composer.lock /app/

# A single character change in this command will trigger a new
# composer install
RUN composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache

RUN mkdir public && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt && \
    apk add --no-cache apache2 musl && sed -i 's#PidFile "/run/.*#Pidfile /tmp/run/httpd.pid"#g' /etc/apache2/conf.d/mpm.conf && \
    sed -i 's/#LoadModule rewrite_module*/LoadModule rewrite_module/' /etc/apache2/httpd.conf

COPY --chown=nginx . /app/
# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

EXPOSE 80
ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
