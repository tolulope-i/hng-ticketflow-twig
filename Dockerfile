# Use an official PHP runtime with Apache as the base image
FROM php:8.2-apache

# Install any required PHP extensions if needed (e.g., for databases)
# RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your application files into the container
COPY . /var/www/html/

# Expose port 80 to the outside world
EXPOSE 80