# Deploy

## Deploy na Render

A Render não usa o `docker-compose.yml`. O repositório traz um blueprint pronto
(`render.yaml`) configurado para o **plano free**.

### Opção A — 100% free, tudo em Docker (padrão do `render.yaml`) ✅
Um único serviço web roda Apache/PHP **e** o MariaDB no mesmo container
(`docker/render-free.Dockerfile`). O seed é importado automaticamente no boot.

1. No dashboard da Render: **New → Blueprint** e aponte para este repositório
   (ou **New → Web Service → Docker** com *Dockerfile Path* = `docker/render-free.Dockerfile`).
2. Health check já configurado em `/healthz.php`. Nenhuma variável é necessária.
3. Aguarde o deploy. Pronto — custo zero.

> ⚠️ **Limitação do plano free (sem disco persistente):** dados criados em runtime
> (novos cadastros, edições de perfil) são perdidos quando o serviço hiberna
> (~15 min sem tráfego) ou redeploya. Receitas e usuários-demo sempre voltam,
> pois vêm do seed. O free também "dorme": a primeira visita após a hibernação
> demora ~30-60s. Para persistência real, use as opções B ou C.

### Opção B — Web free na Render + MySQL externo (free com persistência)
1. Crie um MySQL 8 gerenciado em um provedor com plano grátis (Aiven, Clever Cloud,
   filess.io etc.) e importe o seed: `mysql -h HOST -P PORTA -u USUARIO -p BANCO < DB_Receitas.sql`.
2. Na Render: **New → Web Service → Docker** (Dockerfile padrão `./Dockerfile`).
3. Em *Environment*, defina: `DB_HOST`, `DB_PORT` (provedores externos costumam usar
   porta diferente de 3306), `DB_NAME`, `DB_USER`, `DB_PASS`.
   Se o provedor exigir TLS (ex.: Aiven), suba o CA como *Secret File* e defina
   `DB_SSL_CA=/etc/secrets/ca.pem` (e `DB_SSL_VERIFY=false` apenas para certificado
   autoassinado).
4. Defina o *Health Check Path* como `/healthz.php` e faça o deploy.

### Opção C — Web + MySQL como serviços separados na Render (plano pago)
Use `docker/db.Dockerfile` num serviço privado com disco em `/var/lib/mysql`
(MySQL 8 com seed automático) e aponte o web com `DB_HOST` = host interno do
serviço, `DB_PASS` = `MYSQL_ROOT_PASSWORD`. Serviços privados e discos exigem
plano pago. Para reimportar o seed, recrie o disco (equivalente ao `down -v`).

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
- Usuários de demonstração do seed: `demo1@example.com` (senha `123456`) e
  `demo2@example.com` (senha `271821`) — as senhas no banco ficam em hash bcrypt.
