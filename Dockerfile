FROM razorpay/armory:wkhtmltopdf-v182 as wkhtmltopdf

FROM razorpay/onggi:php-7.2-apache

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV NODE_NAME=""
ARG GIT_USERNAME

WORKDIR /app

# Hack to load php gnu-libiconv.so
# https://github.com/docker-library/php/issues/240#issuecomment-327992638
RUN set -eux && \
    apk add --allow-untrusted --no-cache \
    # gnu-libiconv is the only loaded from /edge/community. Was earlier /edge/testing
    # Version has not been bumped in repo move.
    --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ gnu-libiconv && \
    apk add --allow-untrusted --no-cache libxrender libx11-dev fontconfig zlib-dev \
    ca-certificates glib ttf-freefont dbus p7zip php7-sockets php7-mysqlnd wkhtmltopdf

COPY --from=wkhtmltopdf /bin/wkhtmltopdf /usr/bin/wkhtmltopdf

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

COPY composer.json composer.lock /app/

# A single character change in this command will trigger a new
# composer install
RUN set -eux && \
    git config --global user.name ${GIT_USERNAME} && \
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
