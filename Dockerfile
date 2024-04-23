# Use the official PHP latest image
FROM php:latest

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    librabbitmq-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libaio1 \
    libaio-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set timezone
ENV TZ="Asia/Kathmandu"

# Install PDO PostgreSQL extension
RUN docker-php-ext-install pdo_pgsql

# Set up Oracle Instant Client
RUN mkdir /opt/oracle && cd /opt/oracle && \
    curl -o instantclient-basic-linux.x64-21.3.0.0.0.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-basic-linux.x64-21.3.0.0.0.zip && \
    curl -o instantclient-sdk-linux.x64-21.3.0.0.0.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-sdk-linux.x64-21.3.0.0.0.zip && \
    unzip instantclient-basic-linux.x64-21.3.0.0.0.zip && \
    unzip instantclient-sdk-linux.x64-21.3.0.0.0.zip && \
    ln -s /opt/oracle/instantclient_21_3/libclntsh.so.21.1 /usr/lib/libclntsh.so && \
    ln -s /opt/oracle/instantclient_21_3/libocci.so.21.1 /usr/lib/libocci.so && \
    echo '/opt/oracle/instantclient_21_3/' | tee -a /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# Install oci8 extension
RUN echo "instantclient,/opt/oracle/instantclient_21_3" | pecl install oci8 && \
    echo "extension=oci8.so" > /usr/local/etc/php/conf.d/oci8.ini

# Install PHP extensions
RUN pecl install amqp \
    && docker-php-ext-enable amqp

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
# CMD ["sh", "run_consumer.sh"]
# CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
