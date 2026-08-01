FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy ALL project files
COPY . .

# Install PHP packages (ignore platform reqs for build compatibility)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Copy .env.example as .env
RUN cp .env.example .env

# Copy Nginx config
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Create required storage directories
RUN mkdir -p storage/app/public/documents \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Create startup script
RUN printf '#!/bin/bash\nphp artisan config:clear\nphp artisan storage:link || true\nphp artisan migrate --force\nphp artisan db:seed --force\nphp-fpm -D\nnginx -g "daemon off;"\n' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
