FROM php:8.2-fpm-alpine

# 1. Accept build arguments from GitHub Actions
ARG APP_ENV=production

WORKDIR /var/www

# 2. Install system dependencies
RUN apk add --no-cache \
    libpng libzip oniguruma icu-libs \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS libpng-dev libzip-dev oniguruma-dev icu-dev linux-headers \
    && docker-php-ext-install pdo_mysql mbstring zip intl bcmath \
    && apk del .build-deps

# 3. Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Copy code (Ensure .dockerignore handles the rest)
COPY . /var/www

# 5. Optimized Install
# Using --no-interaction and --prefer-dist makes builds more stable
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist \
    && rm -rf /root/.composer

# 6. Set Permissions
# We do this as root before switching users
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 7. Security: Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
