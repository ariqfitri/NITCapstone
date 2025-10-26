FROM php:8.2-apache

# Install PDO MySQL and mysqli extensions for PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite (optional)
RUN a2enmod rewrite

# Source code will be mounted via docker-compose volumes, so no COPY needed
