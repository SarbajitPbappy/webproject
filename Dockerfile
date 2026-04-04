FROM php:8.2-apache

# Single MPM only (prefork + mod_php). Fixes: AH00534 More than one MPM loaded.
RUN a2dismod mpm_event 2>/dev/null || true \
    && a2dismod mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

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

