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

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction || \
    composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Configure Apache - Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Create and set permissions for session directory
RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && chmod -R 755 /var/lib/php/sessions

# Set PHP configuration for sessions
RUN echo "session.save_path = \"/var/lib/php/sessions\"" > /usr/local/etc/php/conf.d/sessions.ini

# Enable PHP error logging (not display for security)
RUN echo "log_errors = On" > /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/error-logging.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

# Expose port 80
EXPOSE 80
