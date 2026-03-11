FROM php:8.2-apache

# Install system dependencies, OpenSSL, and certificates
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libssl-dev \
    openssl \
    ca-certificates \
    libcurl4-openssl-dev \
    pkg-config \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip

# Install MongoDB extension with SSL support (latest stable)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/

# Copy all application files
COPY . .

# Install Composer dependencies with correct MongoDB library version
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

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

# Enable PHP error logging AND display for debugging
RUN echo "log_errors = On" > /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini

# MongoDB configuration for SSL/TLS
RUN echo "mongodb.debug = 0" > /usr/local/etc/php/conf.d/mongodb.ini

# OpenSSL configuration for MongoDB Atlas compatibility
RUN echo "[openssl_init]" > /usr/local/etc/php/conf.d/openssl.ini && \
    echo "ssl_conf=ssl_sect" >> /usr/local/etc/php/conf.d/openssl.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

# Expose port 80
EXPOSE 80
