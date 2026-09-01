FROM php:8.2-apache

# Install MySQL extension for PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy application files to web server root
COPY . /var/www/html/

# Expose server port
EXPOSE 80
