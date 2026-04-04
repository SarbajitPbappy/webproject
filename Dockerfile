FROM php:8.2-apache

# Comprehensive MPM Cleanup ("Scorched Earth")
# This script finds every file in /etc/apache2 and ensures no mpm_ module is loaded as a LoadModule directive
# before we explicitly enable mpm_prefork.
RUN find /etc/apache2 -type f -name "*.load" -o -name "*.conf" -o -name "apache2.conf" | \
    xargs sed -i 's/^LoadModule mpm_/# LoadModule mpm_/g' && \
    a2dismod mpm_event mpm_worker || true && \
    a2enmod mpm_prefork || true

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

# Start Apache in the foreground
CMD ["apache2-foreground"]

