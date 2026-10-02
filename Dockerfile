FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && a2dissite 000-default

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

COPY --chown=www-data:www-data . /var/www/html/

RUN mkdir -p /var/www/html/runtime/session \
    && chown -R www-data:www-data /var/www/html/runtime \
    && chmod -R 755 /var/www/html/runtime

EXPOSE 10000

CMD ["sh", "-c", "php lava migration run && exec apache2-foreground"]
