FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

# Create .env if missing and generate app key
RUN cp -n .env.example .env 2>/dev/null || true
RUN php artisan key:generate --force

# Fix storage permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t public
