FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql opcache \
 && a2enmod rewrite headers expires deflate

COPY docker/php/site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html
