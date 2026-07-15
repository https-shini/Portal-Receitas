# Deploy

## Deploy na Render

A Render não usa o `docker-compose.yml` — web e banco são serviços separados. O
repositório já traz um blueprint pronto (`render.yaml`).

### Opção A — Blueprint (web + MySQL na própria Render, plano pago)
1. No dashboard da Render: **New → Blueprint** e aponte para este repositório.
2. O blueprint cria dois serviços:
   - `portal-receitas` (web, Docker): a porta é ajustada automaticamente — o
     container respeita a variável `PORT` que a Render injeta; health check em `/healthz.php`.
   - `portal-receitas-db` (serviço privado, `docker/db.Dockerfile`): MySQL 8 com
     disco persistente em `/var/lib/mysql`; o seed `DB_Receitas.sql` é importado
     automaticamente no primeiro boot (disco vazio); `MYSQL_ROOT_PASSWORD` é gerada
     pela Render e injetada no web como `DB_PASS`.
3. Aplique o blueprint e aguarde os dois deploys. Pronto.

> Serviços privados e discos exigem plano pago. Para reimportar o seed, apague e
> recrie o disco do serviço `portal-receitas-db` (equivalente ao `down -v` local).

### Opção B — Web na Render (free) + MySQL externo
1. Crie um MySQL 8 gerenciado em qualquer provedor (Aiven, Railway, Clever Cloud etc.)
   e importe o seed: `mysql -h HOST -P PORTA -u USUARIO -p BANCO < DB_Receitas.sql`.
2. Na Render: **New → Web Service → Docker**, aponte para este repositório.
3. Em *Environment*, defina: `DB_HOST`, `DB_PORT` (provedores externos costumam usar
   porta diferente de 3306), `DB_NAME`, `DB_USER`, `DB_PASS`.
4. Defina o *Health Check Path* como `/healthz.php` e faça o deploy.

---

# Deploy com Docker (local/VPS)

Sobe o site + banco (MySQL 8) com um comando. O seed (`DB_Receitas.sql`) é importado
automaticamente na primeira execução, e o `composer install --no-dev --optimize-autoloader`
roda dentro do build da imagem (o `vendor/` não é versionado).

## Requisitos
- Docker e Docker Compose instalados.

## Local (teste)
```bash
cp .env.example .env      # opcional: ajuste a senha e a porta
docker compose up --build -d
```
Acesse: http://localhost:8080

## Variáveis de ambiente (`.env`)
| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `WEB_PORT` | `8080` | Porta pública do site |
| `DB_PASS` | `rootpass` | Senha do root do MySQL (usada pelo web e pelo db) |

O container `web` recebe `DB_HOST=db`, `DB_NAME=tcc_receitas`, `DB_USER=root` e
`DB_PASS` via compose. A aplicação também aceita `DB_PORT` (padrão `3306`) e a
variável `PORT` para a porta do Apache (padrão `80` — a Render injeta a dela).
Fora do Docker (ex.: XAMPP), sem essas variáveis, a aplicação usa os padrões
`localhost` / `root` / senha vazia / banco `tcc_receitas`.

## Produção (VPS)
```bash
git clone https://github.com/https-shini/Portal-Receitas.git && cd Portal-Receitas
cp .env.example .env      # DEFINA uma DB_PASS forte
# para servir na porta 80:  WEB_PORT=80  no .env
docker compose up --build -d
```
Depois aponte seu domínio para o IP do servidor. Para HTTPS, coloque um
reverse proxy (Caddy ou Nginx + Certbot) na frente do container `web`.

## Comandos úteis
```bash
docker compose ps              # estado dos serviços (aguarde db "healthy")
docker compose logs -f web     # logs da aplicação
docker compose logs -f db      # logs do banco
docker compose down            # para os containers (mantém os dados)
docker compose down -v         # para e APAGA o banco (reimporta o seed na próxima subida)
```

## Observações
- O banco só importa o seed quando o volume `dbdata` está vazio (primeira subida).
  Se alterar o `DB_Receitas.sql` e quiser reimportar: `docker compose down -v` e suba de novo.
- Usuários de demonstração do seed: `kk.123@gmail.com` (senha `123456`) e
  `tectutors.123@gmail.com` (senha `271821`) — as senhas no banco ficam em hash bcrypt.
