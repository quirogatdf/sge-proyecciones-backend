FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev nginx gettext-base \
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

# Nginx config template with PORT variable
RUN rm -f /etc/nginx/sites-enabled/default && \
    cat > /etc/nginx/conf.d/app.conf <<'EOF'
server {
    listen 8000;
    server_name _;
    root /var/www/public;

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

# Startup script
RUN cat > /start.sh <<'EOF'
#!/bin/bash
# Ensure log directories exist
mkdir -p /run/php /var/log/nginx /var/lib/nginx
chown -R www-data:www-data /run/php /var/log/nginx /var/lib/nginx

# Start php-fpm
php-fpm8.4 -D

# Start nginx
nginx -g 'daemon off;'
EOF
RUN chmod +x /start.sh

EXPOSE 8000

CMD ["/start.sh"]
