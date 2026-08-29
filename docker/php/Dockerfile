FROM php:7.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install pdo_mysql mysqli zip gd bcmath mbstring exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "upload_max_filesize=20M\npost_max_size=20M" > /usr/local/etc/php/conf.d/upload.ini

WORKDIR /var/www/html
