# Use official PHP Apache image
FROM php:8.2-apache

# Copy all project files into the container
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set the document root to the public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Add your .htaccess file
COPY .htaccess /var/www/html/public/.htaccess
