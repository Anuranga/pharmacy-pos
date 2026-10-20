# Production Dockerfile for Pharmacy POS & Ledger System on Render.com
FROM php:8.1-apache

# Install required system packages and libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions (MySQL + PostgreSQL + standard extensions)
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# Enable Apache rewrite and headers modules
RUN a2enmod rewrite headers

# Install Composer from official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for optimal layer caching
COPY composer.json composer.lock ./

# Install Composer dependencies (excluding dev tools)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source code
COPY . .

# Complete composer autoload generation
RUN composer dump-autoload --optimize --no-dev

# Configure Apache virtual host
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Setup permissions for web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Expose port (Render sets $PORT dynamically, default 80)
EXPOSE 80 10000

# Entrypoint script handles migrations, caching, and starting Apache
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
