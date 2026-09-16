FROM php:8.4-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip opcache pcntl bcmath \
    && pecl install redis-6.3.0 && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/app.ini
COPY healthcheck.php /usr/local/bin/healthcheck.php
RUN mkdir -p /app/public/storage && chown -R www-data:www-data /app/public/storage
COPY entrypoint.sh /usr/local/bin/hrsquare-entrypoint
ENTRYPOINT ["hrsquare-entrypoint"]
CMD ["php-fpm"]
WORKDIR /app
