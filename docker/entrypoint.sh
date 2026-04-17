#!/usr/bin/env bash
set -e

# Wait for Postgres
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}"; do
        sleep 1
    done
    echo "Database is ready."
fi

# Run pre-start Laravel tasks
if [ -f "artisan" ]; then
    # Ensure storage directories exist (volume mount may not have them)
    mkdir -p storage/framework/{cache,sessions,testing,views}
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

    echo "Running migrations..."
    php artisan migrate --force || true
    
    echo "Caching configuration..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    
    # Ensure Octane is installed
    if ! php artisan octane:status > /dev/null 2>&1; then
        echo "Installing Octane..."
        php artisan octane:install --server=frankenphp
    fi

    # Start Octane
    echo "Starting Octane with FrankenPHP..."
    exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
else
    echo "artisan not found, sleeping..."
    sleep infinity
fi
