#!/bin/sh
# ════════════════════════════════════════════════════════════════
# Entrypoint da imagem all-in-one (plano free da Render).
# Sequência: inicializa o datadir do MariaDB (todo boot no free, pois o
# filesystem é efêmero) → sobe o servidor → importa o seed em banco vazio
# → entrega o processo ao Apache via apache-entrypoint.sh (que aplica a
# porta dinâmica PORT).
#
# A aplicação conecta pela SOCKET Unix (DB_SOCKET), não por TCP: no Debian
# desta imagem o MariaDB não abre a porta TCP de forma confiável, mas a
# socket local sempre responde. Por isso o root é criado com autenticação
# por SENHA (--auth-root-authentication-method=normal): assim o processo do
# Apache (www-data) consegue autenticar como root pela socket, o que a
# autenticação unix_socket (padrão do Debian) não permitiria.
# ════════════════════════════════════════════════════════════════
set -e

DATADIR=/var/lib/mysql

if [ ! -d "$DATADIR/mysql" ]; then
    echo "[render-free] Inicializando datadir do MariaDB..."
    mariadb-install-db --user=mysql --datadir="$DATADIR" \
        --auth-root-authentication-method=normal --skip-test-db >/dev/null
fi

echo "[render-free] Iniciando MariaDB..."
mariadbd-safe --user=mysql --datadir="$DATADIR" >/dev/null 2>&1 &

# Aguarda o servidor aceitar conexões pela socket (limite de 60s para o
# healthcheck da plataforma não validar um container quebrado).
i=0
until mariadb-admin ping --silent 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "[render-free] ERRO: MariaDB não subiu em 60s" >&2
        exit 1
    fi
    sleep 1
done
echo "[render-free] MariaDB pronto (socket)."

# Importa o seed apenas quando o banco ainda não existe (o script tem
# CREATE DATABASE IF NOT EXISTS + USE, então basta redirecionar).
if ! mariadb --protocol=socket -u root -e "USE tcc_receitas" 2>/dev/null; then
    echo "[render-free] Importando seed DB_Receitas.sql..."
    mariadb --protocol=socket -u root < /var/www/html/database/DB_Receitas.sql
    echo "[render-free] Seed importado."
fi

echo "[render-free] Banco pronto. Iniciando Apache..."
exec apache-entrypoint.sh
