# DigiPulse Backend - Production image
# Use official FrankenPHP image with PHP 8.5
FROM dunglas/frankenphp:php8.5-bookworm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    zip \
    git \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    pcntl \
    opcache \
    intl \
    zip \
    bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy the application code
# Note: composer install should be handled before or during build if CI is used.
# For local dev, we mount the volume.
COPY . /var/www/html

# Copy and set up the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ensure correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose Octane port
EXPOSE 8000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
