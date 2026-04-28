FROM composer:2 AS composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts

FROM php:8.4-apache
RUN docker-php-ext-install pdo pdo_pgsql && aenmod rewrite

WORKDIR /var/www/html

COPY --from=composer /app/vendor ./vendor
COPY . .

RUN cp .env.example .env 2>/dev/null || true
RUN php artisan key:generate || true

RUN mkdir -p storage/framework/{sessions,views,logs} bootstrap/cache && \
    chmod -R 775 storage bootstrap && \
    chown -R www-data:www-data .

EXPOSE 8080