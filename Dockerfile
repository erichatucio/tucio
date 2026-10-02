FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf \
    && sed -ri '/<Directory \\/var\\/www\\/>/,/<\\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf \
    && sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \\*:80>/<VirtualHost *:10000>/' \
        /etc/apache2/sites-available/000-default.conf

COPY --chown=www-data:www-data . /var/www/html/

RUN mkdir -p /var/www/html/runtime/session \
    && chown -R www-data:www-data /var/www/html/runtime \
    && chmod -R 755 /var/www/html/runtime

EXPOSE 10000
