# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy the PHP script and .htaccess into the container
COPY index.php .htaccess /var/www/html/

# Expose port 80
EXPOSE 80
