FROM php:8.1-apache

RUN set -xe \
    && sed  -i "s/deb.debian.org/mirrors.aliyun.com/g" /etc/apt/sources.list  \
    && sed  -i "s/security.debian.org/mirrors.aliyun.com/g" /etc/apt/sources.list  \
    && apt-get update  \
    && docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql

COPY flag /flag
COPY src /var/www/html
RUN chown www-data:www-data /var/www/html -R