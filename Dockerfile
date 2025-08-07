# ---- Builder Stage ----
FROM php:8.3-fpm-alpine as builder

# Install build dependencies
RUN apk add --no-cache \
    build-base \
    autoconf \
    curl \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip exif pcntl gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.7.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install composer dependencies
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

# ---- Final Stage ----
FROM php:8.3-fpm-alpine

# Install only necessary runtime dependencies
RUN apk add --no-cache \
    libzip \
    postgresql-libs \
    libpng \
    libjpeg-turbo

# Copy extensions from the builder stage
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

# Set working directory
WORKDIR /var/www

# Copy application code and vendor files from the builder stage
COPY --from=builder /var/www .

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port and start php-fpm
EXPOSE 9000
CMD ["php-fpm"]