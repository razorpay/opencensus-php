FROM razorpay/pithos:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

WORKDIR /app

# Hack to load php gnu-libiconv.so
# https://github.com/docker-library/php/issues/240#issuecomment-327992638
RUN apk add --update libxrender libx11-dev fontconfig zlib-dev && \
    apk add --update-cache gnu-libiconv ca-certificates wkhtmltopdf ttf-freefont dbus \
    --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted && \
    cd /tmp && git clone https://github.com/razorpay/docker-alpine-wkhtmltopdf.git && \
    mv docker-alpine-wkhtmltopdf/wkhtmltopdf /usr/bin/wkhtmltopdf && \
    rm -rf docker-alpine-wkhtmltopdf && cd /app

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

COPY composer.json composer.lock /app/

# A single character change in this command will trigger a new
# composer install
RUN composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt && \
    apk add --no-cache apache2 php7-mysqlnd php7-apache2 musl && sed -i 's#PidFile "/run/.*#Pidfile /tmp/run/httpd.pid"#g' /etc/apache2/conf.d/mpm.conf && \
    sed -i 's/#LoadModule rewrite_module*/LoadModule rewrite_module/' /etc/apache2/httpd.conf && \
    mkdir /opt && chown -R apache:www-data /opt

COPY --chown=apache:www-data . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

EXPOSE 80
ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
