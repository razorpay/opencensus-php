FROM php:7.2

RUN apt update
RUN apt install -y \
            autoconf \
            build-essential \
            g++ \
            gcc \
            git \
            jq \
            libc-dev \
            libmemcached-dev \
            libmemcached11 \
            libpq-dev \
            libpqxx-dev \
            make \
            unzip \
            zip \
            zlib1g \
            zlib1g-dev && \
    pecl install memcached && \
    docker-php-ext-enable memcached && \
    docker-php-ext-install pcntl pdo_mysql pdo_pgsql

COPY ext /ext
RUN cd /ext && phpize && ./configure --enable-opencensus && make install
