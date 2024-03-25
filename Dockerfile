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

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-scripts --no-autoloader

# Change user to non-root user
USER www

# Expose port 8000 and start PHP server with Laravel Octane
EXPOSE 8000

# Command to run the consumer
# CMD ["php", "artisan", "rabbitmq:consume", "queue1", "queue2"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]

