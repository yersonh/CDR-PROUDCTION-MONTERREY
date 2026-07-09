FROM php:8.4-cli-alpine AS base

RUN apk add --no-cache \
        git curl zip unzip libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        icu-dev oniguruma-dev sqlite-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_pgsql pdo_sqlite gd zip bcmath intl mbstring exif pcntl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependencias con el código de la app aún sin copiar, para
# aprovechar la caché de capas de Docker cuando solo cambia el código.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
