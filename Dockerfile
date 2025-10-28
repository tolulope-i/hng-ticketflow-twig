FROM php:8.2-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 3. Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# 4. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# 5. Copy the entire project
COPY . /var/www/html/

# 6. Move public files to web root and preserve vendor directory
RUN mv /var/www/html/public /tmp/public && \
    rm -rf /var/www/html/* && \
    mv /tmp/public/* /var/www/html/ && \
    mv /var/www/html/vendor /tmp/vendor 2>/dev/null || true && \
    mv /tmp/vendor /var/www/html/ 2>/dev/null || true && \
    rm -rf /tmp/public /tmp/vendor

# 7. Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# 8. Expose port 80
EXPOSE 80