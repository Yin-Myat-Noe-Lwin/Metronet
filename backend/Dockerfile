FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev \
    librdkafka-dev \
    pkg-config \
    g++ gcc make autoconf \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql zip

RUN pecl install redis \
    && pecl install rdkafka \
    && docker-php-ext-enable redis rdkafka

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

COPY docker/supervisord.conf /etc/supervisor.conf

ENV COMPOSER_MEMORY_LIMIT=-1

RUN composer install --no-interaction --no-progress --no-scripts

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
