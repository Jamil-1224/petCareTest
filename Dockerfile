FROM php:8.2-apache

# Install system dependencies and certificates
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libssl-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/

# Copy all application files
COPY . .

# Install Composer dependencies with verbose output
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction || \
    (echo "Composer install failed. Trying with --ignore-platform-reqs..." && \
     composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --ignore-platform-reqs)

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
