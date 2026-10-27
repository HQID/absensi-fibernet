#!/usr/bin/env bash
set -e

# Port configuration (default to 80 if PORT is not set by host)
PORT="${PORT:-80}"
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/sites-available/default

# Generate storage link
php artisan storage:link --force || true

# Discover packages & cache configurations
php artisan package:discover --ansi || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run database migrations
php artisan migrate --force || true

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground to keep container running
nginx -g "daemon off;"
