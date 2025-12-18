FROM php:8.2-fpm-alpine

# 1. Build arguments
ARG APP_ENV=production
ARG APP_DEBUG=false

# 2. Set environment variables
ENV APP_ENV=${APP_ENV}
ENV APP_DEBUG=${APP_DEBUG}

WORKDIR /var/www

# 3. Install system dependencies
RUN apk add --no-cache \
    libpng libzip oniguruma icu-libs \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS libpng-dev libzip-dev oniguruma-dev icu-dev linux-headers \
    && docker-php-ext-install pdo_mysql mbstring zip intl bcmath \
    && apk del .build-deps

# 4. Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Copy application source
COPY . .

# 6. Install PHP dependencies (Laravel-friendly)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# 7. Laravel permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# 8. Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
