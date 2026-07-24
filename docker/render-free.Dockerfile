# ════════════════════════════════════════════════════════════════
# Imagem "all-in-one" para o PLANO FREE da Render: Apache/PHP 8.2 e
# MariaDB no MESMO container, com o seed importado no boot.
#
# Trade-off deliberado (ADR-004 em docs/backend.md): o plano free não
# oferece disco persistente nem serviço privado, então os dados criados
# em runtime (cadastros, edições) são perdidos quando o serviço hiberna
# ou redeploya — receitas e usuários-demo sempre voltam pelo seed.
# Alternativas com persistência: DEPLOY.md.
# ════════════════════════════════════════════════════════════════
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Restringe o Apache ao docroot public/ — nada fora dele é servido.
# mod_rewrite + AllowOverride All habilitam o public/.htaccess (URLs amigáveis
# de receita); o AllowOverride é alterado só no bloco <Directory /var/www/>.
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && a2enmod headers rewrite

# MariaDB dimensionado com folga para os 512 MB do plano free (dividido com
# Apache/PHP). Só a socket é usada, mas mantemos bind 127.0.0.1 (nunca
# exposto). performance_schema OFF e buffers enxutos reduzem a pegada de RAM
# a ~80–120 MB, evitando OOM (que derrubaria o banco após o seed).
RUN printf '%s\n' \
    '[mysqld]' \
    'bind-address = 127.0.0.1' \
    'skip-name-resolve' \
    'performance_schema = OFF' \
    'innodb_buffer_pool_size = 32M' \
    'innodb_buffer_pool_chunk_size = 32M' \
    'innodb_log_buffer_size = 8M' \
    'key_buffer_size = 8M' \
    'tmp_table_size = 8M' \
    'max_heap_table_size = 8M' \
    'sort_buffer_size = 512K' \
    'read_buffer_size = 256K' \
    'join_buffer_size = 256K' \
    'thread_cache_size = 4' \
    'table_open_cache = 64' \
    'max_connections = 20' \
    > /etc/mysql/mariadb.conf.d/99-render-free.cnf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências antes do código-fonte para aproveitar o cache de camadas.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . /var/www/html/
RUN composer dump-autoload --optimize --no-dev \
    && cp docker/security-headers.conf /etc/apache2/conf-enabled/security-headers.conf \
    && chown -R www-data:www-data /var/www/html \
    && cp docker/render-free-entrypoint.sh /usr/local/bin/render-free-entrypoint.sh \
    && cp docker/apache-entrypoint.sh /usr/local/bin/apache-entrypoint.sh \
    && chmod +x /usr/local/bin/render-free-entrypoint.sh /usr/local/bin/apache-entrypoint.sh

EXPOSE 80

# A aplicação conecta pela SOCKET Unix do MariaDB (DB_SOCKET), não por TCP:
# no Debian (base desta imagem) o MariaDB não abre a porta TCP de forma
# confiável, mas a socket local sempre funciona (é o caminho do seed e do
# healthcheck). DB_SOCKET tem precedência sobre DB_HOST/DB_PORT no
# PdoConnectionFactory. root autentica por senha vazia (ver entrypoint).
ENV DB_SOCKET=/run/mysqld/mysqld.sock \
    DB_NAME=tcc_receitas \
    DB_USER=root \
    DB_PASS="" \
    APP_DEMO_MODE=1

ENTRYPOINT ["render-free-entrypoint.sh"]
