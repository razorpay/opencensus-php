FROM razorpay/docker:base-nginx-php7-yarn

COPY . /app/

RUN chown -R nginx.nginx /app

COPY ./dockerconf/entrypoint.sh /entrypoint.sh

WORKDIR /app

ARG GIT_TOKEN

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-interaction

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
