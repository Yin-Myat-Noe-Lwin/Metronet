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
    gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
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

COPY backend/composer.json backend/composer.lock ./

COPY backend /app

RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader

RUN composer dump-autoload --optimize

COPY backend/docker/supervisord.conf /etc/supervisor.conf

ENV COMPOSER_MEMORY_LIMIT=-1

RUN mkdir -p /app/storage/logs \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Frontend
COPY frontend /app/frontend

WORKDIR /app/frontend

RUN npm install
RUN npm run build


WORKDIR /app

EXPOSE 9000

CMD ["php-fpm"]
