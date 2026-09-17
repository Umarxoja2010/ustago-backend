#!/bin/sh
set -e

cd /var/www/html

# Automatically run database migrations on container startup
php artisan migrate --force

# Hand off to the base image startup script (Nginx + PHP-FPM)
exec /start.sh
