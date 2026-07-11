# Start from the official PHP-FPM image
FROM php:8.4-fpm-alpine

# Run apk update to ensure packages are up to date
RUN apk update

# Install the PostgreSQL client library
RUN apk add --no-cache libpq-dev

# Install the pdo_pgsql extension so PHP can talk to PostgreSQL
# docker-php-ext-install is a helper script that comes with the official PHP image
RUN docker-php-ext-install pdo pdo_pgsql

# Install the Redis extension via PECL
# --no-cache keeps the image size down
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

# Set the working directory inside the container
WORKDIR /var/www/api