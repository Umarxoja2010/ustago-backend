#!/bin/sh
set -e
cd /var/www/html

# Automatically run database migrations on container startup
php artisan migrate --force

# Automatically seed baseline services and admin user
php artisan db:seed --class=ServiceSeeder --force
php artisan db:seed --class=AdminSeeder --force

# Hand off to the base image startup script (Nginx + PHP-FPM)
exec /start.sh
