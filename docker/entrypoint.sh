#!/bin/bash
set -e

echo "=== Symfony Entrypoint Script ==="

# Generate APP_SECRET if not set or empty
if [ -z "$APP_SECRET" ] || [ "$APP_SECRET" = "change_me_to_random_secret" ]; then
    export APP_SECRET=$(php -r "echo bin2hex(random_bytes(16));")
    echo "Generated APP_SECRET: $APP_SECRET"
fi

# Build assets (must run before DB checks as it doesn't need DB)
echo "Installing importmap packages..."
php bin/console importmap:install || true

echo "Building Tailwind CSS..."
php bin/console tailwind:build --minify || true

echo "Compiling asset map..."
php bin/console asset-map:compile || true

# Wait for database to be ready
echo "Waiting for database to be ready..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Create database if it doesn't exist
echo "Creating database if not exists..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Run migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "=== Initialization completed ==="

# Execute the CMD from Dockerfile (php-fpm -F)
exec "$@"
