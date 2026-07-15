#!/bin/sh
# Sobe o MariaDB embutido (127.0.0.1), importa o seed em banco vazio e então
# inicia o Apache respeitando a variável PORT (via apache-entrypoint.sh).
set -e

DATADIR=/var/lib/mysql

# Inicializa o datadir na primeira execução (free tier: a cada boot do container)
if [ ! -d "$DATADIR/mysql" ]; then
    echo "[render-free] Inicializando datadir do MariaDB..."
    mariadb-install-db --user=mysql --datadir="$DATADIR" --skip-test-db >/dev/null
fi

echo "[render-free] Iniciando MariaDB..."
mariadbd-safe --user=mysql --datadir="$DATADIR" >/dev/null 2>&1 &

i=0
until mariadb-admin ping --silent 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "[render-free] ERRO: MariaDB não subiu em 60s" >&2
        exit 1
    fi
    sleep 1
done

# A aplicação conecta via TCP (127.0.0.1) como root sem senha — acessível apenas
# de dentro do container (bind-address 127.0.0.1); nada é exposto externamente.
mariadb --protocol=socket -u root -e \
    "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Importa o seed apenas se o banco ainda não existe (o dump tem CREATE DATABASE IF NOT EXISTS + USE)
if ! mariadb --protocol=socket -u root -e "USE tcc_receitas" 2>/dev/null; then
    echo "[render-free] Importando seed DB_Receitas.sql..."
    mariadb --protocol=socket -u root < /var/www/html/DB_Receitas.sql
    echo "[render-free] Seed importado."
fi

echo "[render-free] Banco pronto. Iniciando Apache..."
exec apache-entrypoint.sh
