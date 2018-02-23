FROM razorpay/containers:base-php7

ARG GIT_COMMIT_HASH
ARG GIT_TOKEN
ENV GIT_COMMIT_HASH=${GIT_COMMIT_HASH}
ENV NR_INSTALL_SILENT true

COPY . /app/

RUN chown -R apache.www-data /app && \
    sed -i 's#PidFile "/run/.*#Pidfile /tmp/run/httpd.pid"#g' /etc/apache2/conf.d/mpm.conf && \
    sed -i 's/#LoadModule rewrite_module*/LoadModule rewrite_module/' /etc/apache2/httpd.conf

COPY ./dockerconf/entrypoint.sh /entrypoint.sh

WORKDIR /app

RUN apk --update add python py-pip openssl ca-certificates && \
    apk --update add --virtual build-dependencies python-dev libffi-dev openssl-dev build-base  && \
    wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.2.0/dumb-init_1.2.0_amd64 && \
    chmod +x /usr/local/bin/dumb-init && \
    pip install razorpay.alohomora==0.4 && \
    apk del build-dependencies          && \
    rm -rf /var/cache/apk/*

## TODO: move the newrelic install to base-nginx-php7 image
RUN composer config -g github-oauth.github.com ${GIT_TOKEN} \
    && composer install --no-interaction \
    && mkdir /opt && cd /opt \
    && wget https://download.newrelic.com/php_agent/release/newrelic-php5-7.7.0.203-linux-musl.tar.gz \
    && tar -xzvf newrelic-php5-7.7.0.203-linux-musl.tar.gz \
    && ./newrelic-php5-7.7.0.203-linux-musl/newrelic-install install

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dumb-init", "--"]
