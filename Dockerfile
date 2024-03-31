# Use the official PHP latest image
FROM php:latest

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    librabbitmq-dev \
    libssl-dev \
    libcurl4-openssl-dev

# Install PHP extensions
RUN pecl install amqp && docker-php-ext-enable amqp

# Set timezone
ENV TZ="Asia/Kathmandu"

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
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-req=ext-sockets

# Change user to non-root user
USER www

# Expose port 8000
# EXPOSE 8000

# Command to run the consumer
# CMD ["sh", "run_consumer.sh"]
# CMD ["php", "artisan", "serve", "--host=0.0.0.0"]

