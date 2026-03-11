FROM php:8.2-apache

# Install MongoDB Extension
RUN pecl install mongodb
RUN docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
