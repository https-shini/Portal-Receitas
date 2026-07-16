# ════════════════════════════════════════════════════════════════
# Imagem principal da aplicação — Apache + PHP 8.2 (docroot public/)
# Usada pelo docker-compose local/VPS e pelos deploys com banco separado.
# O MariaDB embutido do plano free da Render usa docker/render-free.Dockerfile.
# ════════════════════════════════════════════════════════════════
FROM php:8.2-apache

# pdo_mysql é a única extensão exigida pela aplicação; php.ini-production
# desliga display_errors e ajusta a imagem para produção.
RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Restringe o Apache ao docroot public/ — nada fora dele é servido.
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências antes do código-fonte: o cache desta camada só invalida
# quando composer.json/lock mudam, acelerando rebuilds.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . /var/www/html/
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html \
    && cp docker/apache-entrypoint.sh /usr/local/bin/apache-entrypoint.sh \
    && chmod +x /usr/local/bin/apache-entrypoint.sh

EXPOSE 80

# O entrypoint ajusta o Apache à variável PORT quando a plataforma a injeta
# (ex.: Render); sem PORT, mantém a porta 80 do compose local.
ENTRYPOINT ["apache-entrypoint.sh"]
