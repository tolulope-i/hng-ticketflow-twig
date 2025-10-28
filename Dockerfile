FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files first (for better Docker caching)
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of your project
COPY . /var/www/html/

# Move public files to web root and preserve vendor directory
RUN mv /var/www/html/public /tmp/public && \
    rm -rf /var/www/html/* && \
    mv /tmp/public/* /var/www/html/ && \
    mv /var/www/html/vendor /tmp/vendor && \
    mv /tmp/vendor /var/www/html/ && \
    rm -rf /tmp/public /tmp/vendor

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache rewrite module
RUN a2enmod rewrite

# Update Apache configuration to point to public directory
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Verify the structure
RUN ls -la /var/www/html/

EXPOSE 80