FROM razorpay/pithos:rzp-php7.0-nginx

ARG GIT_COMMIT_HASH
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}
ARG GIT_TOKEN

COPY . /app/

RUN chown -R nginx.nginx /app

COPY ./dockerconf/entrypoint.sh /entrypoint.sh

WORKDIR /app

apk add --virtual .node-deps --update nodejs nodejs-npm

RUN chown -R nginx.nginx /app && \
    composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-interaction && \
    npm install && \
    npm run build

RUN apk del .node-deps

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
