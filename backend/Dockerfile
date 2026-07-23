# FROM php:8.3-fpm
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev \
    librdkafka-dev \
    pkg-config \
    g++ gcc make autoconf \
    supervisor \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl channel-update pecl.php.net \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

COPY . /var/www

RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader

RUN composer dump-autoload --optimize

COPY docker/supervisord.conf /etc/supervisor.conf

ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
