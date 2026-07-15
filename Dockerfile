FROM php:8.2-apache

# Extensões que o projeto usa (PDO MySQL)
RUN docker-php-ext-install pdo_mysql mysqli

# Copia o código para o docroot do Apache
COPY . /var/www/html/

# Permissões corretas para o Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
