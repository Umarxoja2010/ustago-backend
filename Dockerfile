FROM richarvey/nginx-php-fpm:3.1.6

# Set environment variables for Render and Nginx/PHP-FPM
ENV WEBROOT=/var/www/html/public \
    PHP_ERRORS_STDERR=1 \
    RUN_SCRIPTS=1 \
    REAL_IP_HEADER=1 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    SKIP_COMPOSER=1

WORKDIR /var/www/html

# Copy all project files
COPY . .

# Ensure all required storage, cache, and database directories exist with proper permissions
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/testing \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/www/html/database && \
    touch /var/www/html/database/database.sqlite && \
    chown -R nginx:nginx /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Install production composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]
