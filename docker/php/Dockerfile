FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        git \
        unzip \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        mysql-client \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        mbstring \
        zip \
        bcmath \
        sockets \
        opcache \
    && pecl install pcntl \
    && docker-php-ext-enable pcntl \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/backend

