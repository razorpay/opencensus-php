FROM razorpay/onggi:php-base

COPY ext /ext

RUN cd /ext && phpize && ./configure --enable-opencensus && make install
