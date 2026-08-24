FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev nginx \
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

# Nginx: listen on PORT env variable (default 8080)
RUN rm -f /etc/nginx/sites-enabled/default && \
    cat > /etc/nginx/sites-available/app <<'EOF'
server {
    listen ${PORT:-8080};
    server_name _;
    root /var/www/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
RUN ln -sf /etc/nginx/sites-available/app /etc/nginx/sites-enabled/app

EXPOSE ${PORT:-8080}

CMD service php8.4-fpm start && nginx -g 'daemon off;'
