#!/bin/sh
# Ajusta o Apache para a porta injetada pelo ambiente (ex.: Render define PORT).
# Sem PORT definido, mantém a porta 80 (docker compose local).
set -e

if [ -n "${PORT}" ] && [ "${PORT}" != "80" ]; then
    sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

exec docker-php-entrypoint apache2-foreground
