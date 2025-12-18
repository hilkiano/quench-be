FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    bash \
    icu \
    libpng \
    libzip \
    oniguruma \
    mysql-client \
 && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
 && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    bcmath \
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
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Use non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
