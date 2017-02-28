FROM razorpay/docker:base-php7

COPY . /app/

RUN chown -R apache.www-data /app && \
    sed -i 's#PidFile "/run/.*#Pidfile /tmp/run/httpd.pid"#g' /etc/apache2/conf.d/mpm.conf && \
    sed -i 's/#LoadModule rewrite_module*/LoadModule rewrite_module/' /etc/apache2/httpd.conf

COPY ./dockerconf/entrypoint.sh /entrypoint.sh

WORKDIR /app

ARG GIT_TOKEN

RUN composer config -g github-oauth.github.com ${GIT_TOKEN} && \
    composer install --no-interaction

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
