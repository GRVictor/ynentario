#!/bin/sh
set -e

# Create .env from .env.example if it does not exist
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    echo ">> Creando .env desde .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Ensure storage and bootstrap/cache directories exist with proper write permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec "$@"
