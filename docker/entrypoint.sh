#!/bin/bash
set -e

# Default PORT to 80 if not provided by Render
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port: $PORT"

# Replace ports in Apache configuration
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf

# Ensure storage directories exist with write permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if not already created
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link || true
fi

# Run database migrations if RUN_MIGRATIONS environment variable is true
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration encountered an issue (check DB connection credentials)."
    
    # Optionally seed database if SEED_DATABASE is true
    if [ "${SEED_DATABASE:-false}" = "true" ]; then
        echo "Seeding initial database tables..."
        php artisan db:seed --force || echo "Database seeding completed or skipped."
    fi
fi

# Optimize application cache in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching routes and views for production performance..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "Starting Apache Web Server..."
exec apache2-foreground
