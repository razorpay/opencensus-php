FROM razorpay/pithos:rzp-alpine3.6-php7.0-nginx

ARG GIT_COMMIT_HASH
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}
ARG GIT_TOKEN

COPY . /app/

RUN apk add --update lsof && \
    apk add --virtual nodejs-deps nodejs-current nodejs-npm && \
    chown -R nginx.nginx /app

COPY dockerconf/entrypoint.sh /entrypoint.sh

COPY dockerconf/dashboard.conf /etc/nginx/conf.d/default.conf

WORKDIR /app

RUN chown -R nginx.nginx /app && \
    composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-dev --no-interaction && \
    npm install && \
    cd web && npm install && cd .. && \
    npm run build && \
    rm -rf .composer && \
    apk del nodejs-deps

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
