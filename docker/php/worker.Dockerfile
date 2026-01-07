FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
  git unzip curl libzip-dev supervisor ffmpeg \
  && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord"]
