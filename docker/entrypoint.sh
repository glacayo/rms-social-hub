#!/bin/sh
set -e

echo "==> RMS Social Hub — Starting container..."

# Create log directories
mkdir -p /var/log/php /var/log/supervisor /var/log/nginx

# Wait for the database to be ready
echo "==> Waiting for database..."
until php -r "
    \$pdo = new PDO(
        'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'connected';
" 2>/dev/null; do
  echo "    Database not ready — retrying in 3s..."
  sleep 3
done
echo "==> Database ready."

cd /var/www/html

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# Clear and rebuild caches
echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions (in case volume mounts changed them)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Starting services via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
