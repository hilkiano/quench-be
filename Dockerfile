FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies (permanent)
RUN apk add --no-cache \
    bash \
    icu \
    libpng \
    libjpeg-turbo \
    libwebp \
    libzip \
    oniguruma \
    mysql-client \
    imagemagick \
    libgomp

# Install build dependencies, compile extensions, and cleanup
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    imagemagick-dev \
 && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    bcmath \
 # FIX: Force install a specific version or use the master branch if PECL fails
 && pecl install imagick-3.7.0 \
 && docker-php-ext-enable imagick \
 && apk del .build-deps

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source
COPY . .

# Install PHP dependencies (production)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Fix permissions
# Note: We do this as root before switching to the non-root user
USER root
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Use non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
