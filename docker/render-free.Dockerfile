# Imagem "all-in-one" para o PLANO FREE da Render: Apache/PHP + MariaDB no MESMO
# container, com o seed importado automaticamente no boot.
#
# ATENÇÃO: o plano free da Render não tem disco persistente. Os dados criados em
# runtime (novos cadastros, edições de perfil) são PERDIDOS quando o serviço
# hiberna ou faz redeploy — as receitas e os usuários-demo sempre voltam, pois
# vêm do seed (DB_Receitas.sql). Para dados persistentes, use o docker-compose
# local/VPS ou um MySQL externo (veja DEPLOY.md).
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Apache serve apenas o docroot public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# MariaDB enxuto para caber nos 512 MB do plano free (uso interno apenas: 127.0.0.1)
RUN printf '[mysqld]\nbind-address = 127.0.0.1\nperformance_schema = OFF\ninnodb_buffer_pool_size = 64M\nmax_connections = 30\n' \
    > /etc/mysql/mariadb.conf.d/99-render-free.cnf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . /var/www/html/
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html \
    && cp docker/render-free-entrypoint.sh /usr/local/bin/render-free-entrypoint.sh \
    && cp docker/apache-entrypoint.sh /usr/local/bin/apache-entrypoint.sh \
    && chmod +x /usr/local/bin/render-free-entrypoint.sh /usr/local/bin/apache-entrypoint.sh

EXPOSE 80

# 127.0.0.1 (e não "localhost") força TCP no PDO — o socket unix do MariaDB fica em outro caminho
ENV DB_HOST=127.0.0.1 \
    DB_NAME=tcc_receitas \
    DB_USER=root \
    DB_PASS=""

ENTRYPOINT ["render-free-entrypoint.sh"]
