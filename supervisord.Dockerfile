FROM php:8.3-cli

ENV TZ=Asia/Kathmandu

# Install system dependencies
RUN apt-get update && apt-get install -y \
    supervisor \
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

# Set working directory
WORKDIR /var/www/html

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www/html

# Change current user to www
USER www

# Expose port 8000 and start php-fpm server
EXPOSE 8000

COPY supervisord/supervisord.conf /etc/supervisord.conf

ENTRYPOINT ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
