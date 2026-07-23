#!/bin/sh
# ════════════════════════════════════════════════════════════════
# Entrypoint da imagem all-in-one (plano free da Render).
# Sequência: inicializa o datadir do MariaDB (todo boot no free, pois o
# filesystem é efêmero) → sobe o servidor → cria o acesso TCP local da
# aplicação → espera o MariaDB responder PELO MESMO CAMINHO do app (TCP
# 127.0.0.1) → importa o seed em banco vazio → entrega o processo ao
# Apache via apache-entrypoint.sh (que aplica a porta dinâmica PORT).
# ════════════════════════════════════════════════════════════════
set -e

DATADIR=/var/lib/mysql

if [ ! -d "$DATADIR/mysql" ]; then
    echo "[render-free] Inicializando datadir do MariaDB..."
    # --auth-root-authentication-method=normal: o root usa senha (vazia) em
    # vez de unix_socket. No Debian (base php:8.2-apache) o padrão do
    # mariadb-install-db é unix_socket, que autentica só pela socket local e
    # QUEBRA a conexão TCP que a aplicação (PDO) usa — origem do 503.
    mariadb-install-db --user=mysql --datadir="$DATADIR" \
        --auth-root-authentication-method=normal --skip-test-db >/dev/null
fi

echo "[render-free] Iniciando MariaDB..."
mariadbd-safe --user=mysql --datadir="$DATADIR" >/dev/null 2>&1 &

# 1) Espera a socket local aceitar conexões (para criar o usuário do app).
i=0
until mariadb-admin ping --silent 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "[render-free] ERRO: MariaDB não subiu (socket) em 60s" >&2
        exit 1
    fi
    sleep 1
done

# A aplicação conecta por TCP (127.0.0.1) como root sem senha. Seguro neste
# contexto: bind-address 127.0.0.1 impede qualquer acesso externo. CREATE OR
# REPLACE garante a conta com mysql_native_password e senha vazia mesmo que
# uma root@127.0.0.1 herdada já exista com outro método de autenticação.
mariadb --protocol=socket -u root -e \
    "CREATE OR REPLACE USER 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# 2) Espera o MariaDB responder PELO MESMO CAMINHO do app (TCP 127.0.0.1).
# Só assim garantimos que a aplicação vai conectar — a checagem por socket
# acima não prova que o TCP subiu.
i=0
until mariadb -h 127.0.0.1 -P 3306 -u root -e "SELECT 1" >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "[render-free] ERRO: MariaDB não respondeu via TCP 127.0.0.1 em 60s" >&2
        exit 1
    fi
    sleep 1
done
echo "[render-free] MariaDB acessível via TCP 127.0.0.1."

# Importa o seed apenas quando o banco ainda não existe (o script tem
# CREATE DATABASE IF NOT EXISTS + USE, então basta redirecionar).
if ! mariadb -h 127.0.0.1 -u root -e "USE tcc_receitas" 2>/dev/null; then
    echo "[render-free] Importando seed DB_Receitas.sql..."
    mariadb -h 127.0.0.1 -u root < /var/www/html/database/DB_Receitas.sql
    echo "[render-free] Seed importado."
fi

echo "[render-free] Banco pronto. Iniciando Apache..."
exec apache-entrypoint.sh
