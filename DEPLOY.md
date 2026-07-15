# Deploy com Docker

Sobe o site + banco (MySQL) com um comando. O seed (`DB_Receitas.sql`) é importado
automaticamente na primeira execução.

## Requisitos
- Docker e Docker Compose instalados.

## Local (teste)
```bash
cp .env.example .env      # opcional: ajuste a senha e a porta
docker compose up -d --build
```
Acesse: http://localhost:8080

## Produção (VPS)
```bash
git clone <seu-repo> && cd TCC-Site-Receitas
cp .env.example .env      # DEFINA uma DB_PASS forte
# para servir na porta 80:  WEB_PORT=80  no .env
docker compose up -d --build
```
Depois aponte seu domínio para o IP do servidor. Para HTTPS, coloque um
reverse proxy (Caddy ou Nginx + Certbot) na frente do container `web`.

## Comandos úteis
```bash
docker compose logs -f web     # logs da aplicação
docker compose logs -f db      # logs do banco
docker compose down            # para os containers (mantém os dados)
docker compose down -v         # para e APAGA o banco (reimporta o seed na próxima subida)
```

## Observações
- O banco só importa o seed quando o volume `dbdata` está vazio (primeira subida).
  Se alterar o `DB_Receitas.sql` e quiser reimportar: `docker compose down -v` e suba de novo.
- As credenciais do banco vêm de variáveis de ambiente. No XAMPP local, sem essas
  variáveis, o `conexao.php` usa os valores antigos (localhost / root / sem senha).
