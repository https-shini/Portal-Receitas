FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Apache passa a servir apenas o docroot public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências antes de copiar o código para aproveitar o cache de camadas
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . /var/www/html/
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
