FROM razorpay/onggi:php-7.2-apache-api

COPY ext /ext

RUN cd /ext && phpize && ./configure --enable-opencensus && make install  && docker-php-ext-enable opencensus
