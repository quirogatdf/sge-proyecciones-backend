FROM php:8.4-apache

RUN apt-get update && apt-get install -y libpq-dev postgresql-client zip unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2dismod mpm_event \
    && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts

COPY . .

RUN cp .env.example .env 2>/dev/null || true
RUN php artisan key:generate 2>/dev/null || true

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80