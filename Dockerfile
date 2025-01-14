FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    git \
    unzip \
    curl \
    nginx \
    && docker-php-ext-install zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html/metricalo_assignment

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# Copy the rest of the application
COPY . .

# Run composer scripts and optimize
RUN composer dump-autoload --optimize \
    && composer run-script post-install-cmd

# Configure Nginx
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Expose ports for PHP-FPM and Nginx
EXPOSE 9000 8080

# Start PHP-FPM and Nginx
CMD service nginx start && php-fpm
