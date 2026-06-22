FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    bash \
    curl \
    git \
    unzip \
    zlib-dev \
    libxml2-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install intl opcache pcntl zip pdo_mysql bcmath
RUN pecl install redis && docker-php-ext-enable redis

# Use official composer binary from composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source so Symfony scripts can run during composer install
COPY . .

RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Ensure PHP-FPM listens on all interfaces so Nginx can reach it from its own container.
RUN sed -i 's/^;listen = 127.0.0.1:9000$/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
