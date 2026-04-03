FROM php:8.2-apache

# Enable URL rewriting for .htaccess routing
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/public/uploads/students /var/www/html/public/uploads/documents && \
    chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80

