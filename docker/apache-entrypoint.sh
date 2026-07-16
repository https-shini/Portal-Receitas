#!/bin/sh
# ════════════════════════════════════════════════════════════════
# Entrypoint da imagem principal: adapta o Apache à porta dinâmica.
# Plataformas como a Render injetam a variável PORT; quando presente e
# diferente de 80, o Listen e o VirtualHost são reescritos antes do boot.
# Sem PORT (compose local), nada muda.
# ════════════════════════════════════════════════════════════════
set -e

if [ -n "${PORT}" ] && [ "${PORT}" != "80" ]; then
    sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

exec docker-php-entrypoint apache2-foreground
