FROM php:8.3-cli

ENV TZ=Asia/Kathmandu
# Install Supervisor
RUN apt-get update && apt-get install -y supervisor libpq-dev

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
