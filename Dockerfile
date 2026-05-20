# Stage 1: Build & Dependencies
FROM php:8.3-fpm-alpine AS builder

# Install system dependencies needed for Postgres and generic packages
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    git \
    unzip

# Install required PHP extensions for Laravel + Postgres
RUN docker-php-ext-install pdo_pgsql gd

WORKDIR /var/www
COPY . /var/www

# Install production-ready composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Production Runtime
FROM php:8.3-fpm-alpine

# Re-install runtime system dependencies for Postgres
RUN apk add --no-cache postgresql-libs libpng libjpeg-turbo freetype

# Copy extensions and code from builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=builder /var/www /var/www

WORKDIR /var/www

# Strict production permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]