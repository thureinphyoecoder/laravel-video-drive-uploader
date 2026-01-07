FROM php:8.3-fpm

# system dependencies
RUN apt-get update && apt-get install -y \
  git \
  unzip \
  curl \
  libzip-dev \
  ffmpeg \
  && docker-php-ext-install pdo pdo_mysql zip

# install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# working directory inside container
WORKDIR /var/www

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
