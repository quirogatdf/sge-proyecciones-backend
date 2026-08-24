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

# Nginx config template (uses PORT env variable via envsubst)
RUN rm -f /etc/nginx/sites-enabled/default && \
    cat > /etc/nginx/sites-available/app.conf <<'EOF'
server {
    listen ${PORT:-8080};
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

# Startup script that generates nginx config with correct PORT
RUN cat > /start.sh <<'EOF'
#!/bin/bash
export PORT=${PORT:-8080}
envsubst '${PORT}' < /etc/nginx/sites-available/app.conf > /etc/nginx/sites-enabled/app.conf
service php8.4-fpm start
nginx -g 'daemon off;'
EOF
RUN chmod +x /start.sh

EXPOSE ${PORT:-8080}

CMD ["/start.sh"]
