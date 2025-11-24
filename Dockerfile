# Use official PHP image with Apache
FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port (Render will set PORT env variable)
EXPOSE ${PORT:-10000}

# Start PHP built-in server using Render's PORT with router for static files
CMD php -S 0.0.0.0:${PORT:-10000} -t . router.php
