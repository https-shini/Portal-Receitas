#!/bin/sh
# ════════════════════════════════════════════════════════════════
# Entrypoint da imagem all-in-one (plano free da Render).
# Sequência: inicializa o datadir do MariaDB (todo boot no free, pois o
# filesystem é efêmero) → sobe o servidor → cria o acesso TCP local da
# aplicação → importa o seed em banco vazio → entrega o processo ao
# Apache via apache-entrypoint.sh (que aplica a porta dinâmica PORT).
# ════════════════════════════════════════════════════════════════
set -e

DATADIR=/var/lib/mysql

if [ ! -d "$DATADIR/mysql" ]; then
    echo "[render-free] Inicializando datadir do MariaDB..."
    mariadb-install-db --user=mysql --datadir="$DATADIR" --skip-test-db >/dev/null
fi

echo "[render-free] Iniciando MariaDB..."
mariadbd-safe --user=mysql --datadir="$DATADIR" >/dev/null 2>&1 &

# Aguarda o servidor aceitar conexões (limite de 60s para o healthcheck
# da plataforma não validar um container quebrado).
i=0
until mariadb-admin ping --silent 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "[render-free] ERRO: MariaDB não subiu em 60s" >&2
        exit 1
    fi
    sleep 1
done

# O PDO conecta por TCP (127.0.0.1) como root sem senha. Seguro neste
# contexto: o bind-address 127.0.0.1 impede qualquer acesso externo.
# CREATE USER IF NOT EXISTS é necessário porque o mariadb-install-db cria
# root apenas com autenticação por socket unix (root@localhost).
mariadb --protocol=socket -u root -e \
    "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Importa o seed apenas quando o banco ainda não existe (o script tem
# CREATE DATABASE IF NOT EXISTS + USE, então basta redirecionar).
if ! mariadb --protocol=socket -u root -e "USE tcc_receitas" 2>/dev/null; then
    echo "[render-free] Importando seed DB_Receitas.sql..."
    mariadb --protocol=socket -u root < /var/www/html/DB_Receitas.sql
    echo "[render-free] Seed importado."
fi

echo "[render-free] Banco pronto. Iniciando Apache..."
exec apache-entrypoint.sh
