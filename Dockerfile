FROM razorpay/onggi:php-intlphp-7.2-apache

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

WORKDIR /app

# Hack to load php gnu-libiconv.so
# https://github.com/docker-library/php/issues/240#issuecomment-327992638
RUN set -eux && \
    apk add --allow-untrusted --no-cache \
    # gnu-libiconv is the only loaded from /edge/community. Was earlier /edge/testing
    # Version has not been bumped in repo move.
    --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ gnu-libiconv && \
    apk add --allow-untrusted --no-cache libxrender libx11-dev fontconfig zlib-dev \
    ca-certificates glib ttf-freefont dbus p7zip php7-sockets php7-mysqlnd && \
    #https://github.com/gliderlabs/docker-alpine/issues/30#issuecomment-372020089
    update-ca-certificates 2>/dev/null && \
    cd /tmp && git clone https://github.com/razorpay/docker-alpine-wkhtmltopdf.git && \
    mv docker-alpine-wkhtmltopdf/wkhtmltopdf /usr/bin/wkhtmltopdf && \
    chmod +x /usr/bin/wkhtmltopdf && \
    rm -rf docker-alpine-wkhtmltopdf

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

COPY composer.json composer.lock /app/

# A single character change in this command will trigger a new
# composer install
RUN set -eux && \
    composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer global require hirak/prestissimo && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache && \
    mkdir -p public && echo "${GIT_COMMIT_HASH}" > public/commit.txt

COPY --chown=apache:www-data . /app/

RUN cp dockerconf/mpm.conf /etc/apache2/conf.d/mpm.conf && \
    cp dockerconf/default.conf /etc/apache2/conf.d/default.conf && \
    # This step can't run without some classes from above step
    composer dump-autoload -o && php artisan optimize

EXPOSE 80

ENTRYPOINT [ "/usr/bin/dumb-init", "--single-child", "/app/dockerconf/entrypoint.sh"]
