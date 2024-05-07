# Base image for supervisor
FROM gitlab.wlink.com.np:4567/samir.husen/php-cli-oracle-pgsql-base-image:latest

# Set working directory
WORKDIR /var/www/html

# Create non-root user and set permissions
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

# Copy the application code into the container
COPY --chown=www:www . /var/www/html

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Composer dependencies before copying the application code
COPY composer.* ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-req=ext-sockets --ignore-platform-req=ext-oci8

# Change user to non-root user
USER www

# Expose port 8000
# EXPOSE 8000

# Command to run the consumer
# CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
