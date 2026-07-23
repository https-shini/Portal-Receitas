# ════════════════════════════════════════════════════════════════
# MySQL 8 com o seed oficial embutido na imagem.
# Uso: serviço de banco dedicado em plataformas sem bind mount (ex.:
# serviço privado da Render com disco em /var/lib/mysql) — a importação
# ocorre no primeiro boot com o diretório de dados vazio.
# ════════════════════════════════════════════════════════════════
FROM mysql:8.0

COPY database/DB_Receitas.sql /docker-entrypoint-initdb.d/DB_Receitas.sql
