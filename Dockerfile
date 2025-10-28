FROM php:8.2-apache

# Copy everything to Apache's default web root
COPY . /var/www/html/

# Move public files to the correct location and set proper directory structure
RUN mv /var/www/html/public /tmp/public && \
    rm -rf /var/www/html/* && \
    mv /tmp/public/* /var/www/html/ && \
    rm -rf /tmp/public

# Ensure index.php is directly in web root
RUN ls -la /var/www/html/

EXPOSE 80