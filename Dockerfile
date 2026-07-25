FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

RUN a2enmod rewrite

COPY docker/rewrite.conf /etc/apache2/conf-enabled/rewrite.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
