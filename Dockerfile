FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist

FROM php:8.4-apache
RUN aenmod rewrite

COPY --from=composer /app/vendor /var/www/html/vendor
COPY . /var/www/html

RUN cp -rnT vendor/laravel/framework/src/Illuminate/Foundation/Console/Resources/views/storage/framework/sessions /var/www/html/storage/framework/sessions && \
    cp -rnT vendor/laravel/framework/src/Illuminate/Foundation/Console/Resources/views/storage/framework/views /var/www/html/storage/framework/views && \
    cp -rnT vendor/laravel/framework/src/Illuminate/Foundation/Console/Resources/views/storage/logs /var/www/html/storage/logs && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 8080