# DigiPulse Backend - Production image
# Force rebuild to apply entrypoint changes
# Use official FrankenPHP image with PHP 8.5
FROM dunglas/frankenphp:php8.5-bookworm

# Set working directory
WORKDIR /var/www/html

# Install PHP extension installer (handles all build deps automatically)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    pcntl \
    opcache \
    intl \
    zip \
    bcmath \
    redis

# Install additional system tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip zip git \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy the application code
# Note: composer install should be handled before or during build if CI is used.
# For local dev, we mount the volume.
COPY . /var/www/html

# Copy and set up the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ensure correct permissions and required directories
RUN mkdir -p storage/framework/{cache,sessions,testing,views} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose Octane port
EXPOSE 8000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
