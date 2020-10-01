FROM razorpay/armory:wkhtmltopdf-v182 as wkhtmltopdf

FROM razorpay/onggi:php-7.2-apache-api

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ARG GIT_USERNAME

WORKDIR /app

COPY --from=wkhtmltopdf /bin/wkhtmltopdf /usr/bin/wkhtmltopdf

COPY composer.json composer.lock /app/

RUN set -eu && \
    git config --global user.name ${GIT_USERNAME} && \
    composer config -g "github-oauth.github.com" ${GIT_TOKEN} && \
    composer config -g repos.packagist composer "https://packagist.rzp.io" && \
    composer global require hirak/prestissimo && \
    composer install --no-dev --no-interaction --no-autoloader --no-scripts && \
    rm -rf /root/.composer && \
    composer clear-cache && \
    pear config-set php_ini /etc/php7/php.ini && \
    mkdir -p public && echo "${GIT_COMMIT_HASH}" > public/commit.txt

COPY --chown=apache:www-data . /app/

RUN cp dockerconf/default.conf dockerconf/mpm.conf /etc/apache2/conf.d/ && \
    composer dump-autoload -o && \
    php artisan optimize

EXPOSE 80

ENTRYPOINT [ "/usr/bin/dumb-init", "--single-child", "/app/dockerconf/entrypoint.sh"]
