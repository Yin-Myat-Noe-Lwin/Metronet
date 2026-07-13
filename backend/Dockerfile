FROM php:8.3-fpm

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

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader

COPY . /app

RUN composer dump-autoload --optimize

COPY docker/supervisord.conf /etc/supervisor.conf

ENV COMPOSER_MEMORY_LIMIT=-1

RUN mkdir -p /var/log/supervisor \
    && chmod -R 777 /var/log/supervisor \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 /app/storage/logs

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
