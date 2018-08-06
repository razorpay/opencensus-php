FROM razorpay/pithos:rzp-php7.1-nginx

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

WORKDIR /app

# Hack to load php gnu-libiconv.so
# https://github.com/docker-library/php/issues/240#issuecomment-327992638
RUN apk add --allow-untrusted --no-cache \
    --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ \
    libxrender libx11-dev fontconfig zlib-dev gnu-libiconv \
    ca-certificates wkhtmltopdf ttf-freefont dbus p7zip && \
    #https://github.com/gliderlabs/docker-alpine/issues/30#issuecomment-372020089
    update-ca-certificates 2>/dev/null && \
    cd /tmp && git clone https://github.com/razorpay/docker-alpine-wkhtmltopdf.git && \
    mv docker-alpine-wkhtmltopdf/wkhtmltopdf /usr/bin/wkhtmltopdf && \
    rm -rf docker-alpine-wkhtmltopdf

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

COPY composer.json composer.lock /app/

# A single character change in this command will trigger a new
# composer install
RUN composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer global require hirak/prestissimo && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache && \
    echo ${GIT_COMMIT_HASH} > public/commit.txt && \
    apk add --no-cache apache2 apache2-ctl php7-mysqlnd php7-apache2 musl && sed -i 's#PidFile "/run/.*#Pidfile /tmp/run/httpd.pid"#g' /etc/apache2/conf.d/mpm.conf && \
    sed -i 's/#LoadModule rewrite_module*/LoadModule rewrite_module/' /etc/apache2/httpd.conf && \
    mkdir /opt && chown -R apache:www-data /opt

COPY --chown=apache:www-data . /app/

# This step can't run without some classes from above step
RUN composer dump-autoload && php artisan optimize

EXPOSE 80
ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
