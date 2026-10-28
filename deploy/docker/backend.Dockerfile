FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j2 pdo_mysql mbstring bcmath zip pcntl opcache \
    && pecl install redis-6.2.0 && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && mkdir -p storage/app/private/evidence storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
ENTRYPOINT ["sh", "/var/www/html/docker/entrypoint.sh"]
CMD ["php-fpm"]
