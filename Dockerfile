# ==========================================
# Stage 1: Composer Dependencies (Vendor)
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

# ==========================================
# Stage 2: Build Frontend Assets (Vite & Tailwind v4)
# ==========================================
FROM node:22-bookworm-slim AS frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Copy vendor so Tailwind v4 can resolve Livewire Flux CSS & Blade templates
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ==========================================
# Stage 3: Production PHP Application
# ==========================================
FROM php:8.4-fpm-bookworm

# Install required system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions for Laravel & Excel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy vendor from Stage 1
COPY --from=vendor /app/vendor ./vendor

# Copy compiled frontend assets from Stage 2
COPY --from=frontend /app/public/build ./public/build

# Setup Nginx and Entrypoint script
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Set directory permissions for Laravel storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
