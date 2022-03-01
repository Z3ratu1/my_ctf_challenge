FROM php:7.4-apache

RUN set -xe \
    && sed  -i "s/deb.debian.org/mirrors.aliyun.com/g" /etc/apt/sources.list  \
    && sed  -i "s/security.debian.org/mirrors.aliyun.com/g" /etc/apt/sources.list  \
    && apt-get update  \
    && apt-get install -y libzip-dev \
    && docker-php-ext-install zip \
    && chown -R root:root /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir code \
    && chown -R www-data:www-data code \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


COPY flag /flag

WORKDIR /var/www/html

COPY composer.json composer.json

RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist

COPY *.php ./
