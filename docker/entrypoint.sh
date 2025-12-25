#!/bin/bash
set -e

echo "=== Symfony Entrypoint Script ==="

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

echo "=== Database initialization completed ==="

# Execute the CMD from Dockerfile (php-fpm -F)
exec "$@"
