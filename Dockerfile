FROM php:8.2-fpm-alpine

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="10000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    wget \
    zip \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    mysql-client \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    intl \
    zip \
    opcache \
    gd \
    bcmath \
    exif \
    pcntl \
    sockets

# Install APCu (for caching)
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Clean up build dependencies
RUN apk del --no-cache \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/date_timezone.ini \
    && echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory_limit.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/upload_max_filesize.ini \
    && echo "post_max_size=20M" > /usr/local/etc/php/conf.d/post_max_size.ini \
    && echo "max_execution_time=300" > /usr/local/etc/php/conf.d/max_execution_time.ini

# Configure OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=0'; \
    echo 'opcache.enable_cli=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configure APCu
RUN { \
    echo 'apc.enabled=1'; \
    echo 'apc.shm_size=32M'; \
    echo 'apc.ttl=7200'; \
    echo 'apc.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/apcu.ini

# Configure PHP-FPM
RUN { \
    echo '[global]'; \
    echo 'error_log = /proc/self/fd/2'; \
    echo '[www]'; \
    echo 'access.log = /proc/self/fd/2'; \
    echo 'clear_env = no'; \
    echo 'catch_workers_output = yes'; \
    echo 'decorate_workers_output = no'; \
    echo 'listen = 9000'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 20'; \
    echo 'pm.start_servers = 2'; \
    echo 'pm.min_spare_servers = 1'; \
    echo 'pm.max_spare_servers = 3'; \
    } > /usr/local/etc/php-fpm.d/zz-docker.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies (without dev dependencies for production)
# For development, change --no-dev to include dev dependencies
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

# Copy application files
COPY . .

# Copy environment file if it doesn't exist
RUN if [ ! -f .env.local ]; then cp .env .env.local; fi

# Build Tailwind CSS and compile assets
RUN php bin/console tailwind:build --minify \
    && php bin/console asset-map:compile

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Run composer dump-autoload
RUN composer dump-autoload --optimize --classmap-authoritative

# Clear and warm up cache
RUN php bin/console cache:clear --env=prod --no-debug || true \
    && php bin/console cache:warmup --env=prod --no-debug || true

# Expose PHP-FPM port
EXPOSE 9000

# Switch to www-data user
USER www-data

# Start PHP-FPM in foreground mode (required for Docker)
CMD ["php-fpm", "-F"]
