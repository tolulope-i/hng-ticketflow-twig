FROM php:8.2-apache

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

# Verify the structure
RUN ls -la /var/www/html/

EXPOSE 80