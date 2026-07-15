# Imagem do MySQL com o seed embutido — usada no deploy da Render (serviço privado),
# onde não há bind mount como no docker compose local.
FROM mysql:8.0

COPY DB_Receitas.sql /docker-entrypoint-initdb.d/DB_Receitas.sql
