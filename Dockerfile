FROM razorpay/dashboard:ci

# This is the final production image
ARG GIT_COMMIT_HASH
ARG GIT_TOKEN

COPY . /app/

WORKDIR /app

# A lot of cleanup here is useless because these things are already committed
# on the base layer
RUN rm -rf /root/.composer && \
       rm -rf /app/.git && \
	# PHP Stuff
	composer config -g github-oauth.github.com ${GIT_TOKEN} && \
	composer install --no-dev --no-interaction  && \
	# Node stuff
	npm install && \
    npm run build && \
    # Cleanup PHP+Node
    rm -rf /root/.composer && \
    rm -rf /tmp/npm-* && \
    rm -rf /app/node_modules && \
    rm -rf /app/web/node_modules && \
    # Generate /commit.txt
    echo ${GIT_COMMIT_HASH} > public/commit.txt && \
    # Cleanup deps
    apk del nodejs-deps && \
    # TODO: Improve this step so it gets faster
    echo "** Fix file permissions **" && \
    chown -R nginx.nginx /app && \
    # Temporarily enable core dumps and some debug logging
    # on beta-dashboard
    echo "** Copying extra config **" && \
    cp /app/dockerconf/www.conf /etc/php7/php-fpm.d && \
    cp /app/dockerconf/php-fpm.conf /etc/php7/

EXPOSE 80

ENTRYPOINT ["/app/dockerconf/entrypoint.sh"]
