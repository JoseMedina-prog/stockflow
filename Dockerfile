FROM php:8.2-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html