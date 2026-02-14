# Use explicit Alpine 3.20 or 3.21 variant → much more stable for PHP 8.2 extensions in 2025–2026
FROM php:8.3-fpm-alpine3.20

# The rest of your Dockerfile remains almost identical
# ==================================================================

# Combine commands into a single layer...
RUN apk add --no-cache \
    postgresql-dev \
    libpq-dev \
    oniguruma-dev \
    libxml2-dev \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    supervisor \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        zip \
        pcntl \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Cleanup – remove build-time dependencies
    && apk del --no-cache $PHPIZE_DEPS linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

# ========== Stage 2: Install Composer ==========
# Copy Composer binary from the official Composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ========== Stage 3: Prepare application ==========
WORKDIR /var/www/html

# ---- IMPORTANT DOCKER CACHE OPTIMIZATION ----
# Copy composer files first so dependencies are cached unless they change.
COPY composer.json composer.lock ./

# Install PHP dependencies with optimized autoloader.
# --no-dev reduces production size.
# --prefer-dist speeds up installation.
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader

# Now copy the rest of the application.
COPY . .

# Create required Laravel directories and set proper permissions.
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Switch to non-root user for better container security.
USER www-data

# ========== Stage 4: Runtime ==========
# PHP-FPM listens on port 9000.
EXPOSE 9000

CMD ["php-fpm"]
