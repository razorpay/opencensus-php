FROM razorpay/docker:base-nginx-php7-yarn

ARG GIT_COMMIT_HASH
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}
ARG GIT_TOKEN


COPY . /app/

RUN chown -R nginx.nginx /app

COPY ./dockerconf/entrypoint.sh /entrypoint.sh

WORKDIR /app

RUN apk --update add python py-pip openssl ca-certificates && \
    apk --update add --virtual build-dependencies python-dev libffi-dev openssl-dev build-base  && \
    pip install razorpay.alohomora==0.2 && \
    apk del build-dependencies          && \
    rm -rf /var/cache/apk/*

RUN chown -R nginx.nginx /app && \
    composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-interaction && \
    npm install && \
    gulp

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
