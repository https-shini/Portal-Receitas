<img width=100% src="https://capsule-render.vercel.app/api?type=waving&color=gradient&height=120&section=header" alt="Header Wave"/>

<div align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Orbitron&weight=700&size=32&duration=3000&pause=1000&color=FF6B6B&center=true&vCenter=true&width=900&lines=Sua+Jornada+Culin%C3%A1ria+Personalizada;Descubra+Receitas+e+Crie+Sua+Hist%C3%B3ria;Homemade+Gourmet" alt="Título Dinâmico" />
</div>

<div align="center">

[![Status](https://img.shields.io/badge/Status-Concluído-success?style=for-the-badge)](https://github.com/https-shini/Portal-Receitas)
[![License](https://img.shields.io/badge/Licença-MIT-yellow?style=for-the-badge)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](composer.json)
[![Arquitetura](https://img.shields.io/badge/Clean-Architecture-2d3436?style=for-the-badge)](docs/architecture.md)
[![TCC](https://img.shields.io/badge/TCC-2022→Refactor%202026-blue?style=for-the-badge)](CHANGELOG.md)

</div>

---

## 🍳 Sobre o Projeto

**Homemade Gourmet** é um portal de receitas nascido como TCC (ETEC de Vila Formosa, NOVOTEC, 2022) e, em 2026, reescrito sobre **Clean Architecture** em PHP 8.2, com deploy em Docker/Render, senhas protegidas por bcrypt e testes automatizados.

O que o site faz hoje, exatamente como está no código:

- **Catálogo de receitas** em cards com foto, tempo de preparo e categoria.
- **Detalhe de cada receita** com vídeo do YouTube incorporado (iframe), lista de até 15 ingredientes, porções, calorias e modo de preparo dividido em passos.
- **Busca por ingrediente**: o termo é comparado (`LIKE`) contra as 15 colunas de ingredientes de cada receita.
- **Filtro por categoria**, com seis categorias fixas: Frutos do Mar, Massas, Veganas, Salgados, Doces e Carnes.
- **Cadastro e login de usuários** com senha protegida por `password_hash`/`password_verify` (bcrypt, `PASSWORD_DEFAULT`) — tela unificada com painel deslizante, medidor de força de senha e validação no servidor (mín. 8 caracteres com letra e número), UX inspirada no [AuthService](https://github.com/https-shini/AuthService), com API JSON própria (`/api/login.php`, `/api/register.php`, `/api/me.php`, `/api/logout.php`).
- **Perfil do usuário** com edição de nome, e-mail e senha, protegido por guard de sessão.
- **Logout** com encerramento de sessão.
- **Resiliência de deploy**: página amigável de indisponibilidade (HTTP 503) quando o banco está fora, e endpoint `healthz.php` (HTTP 200, sem tocar o banco) para health check das plataformas.

### 🗺️ Roadmap (ainda não implementado)

Ideias da concepção original do TCC que **não existem** no código atual e ficam como evolução futura: engine de recomendação por preferências, calculadora de calorias em tempo real por porção, sistema de favoritos e avaliações, e camada de comunidade entre usuários.

---

## 🛠️ Stack

<div align="center">

| Camada | Tecnologia |
|--------|-----------|
| **Interface** | HTML5 semântico, CSS3 e JavaScript (vanilla) · Design System próprio (Liquid Glass, tokens, 8pt grid, temas claro/escuro, WCAG 2.2 AA) |
| **Backend** | PHP 8.2+ · Clean Architecture · PSR-4 (`App\ → src/`) · Composer |
| **Dados** | MySQL 8 / MariaDB · PDO com prepared statements (`EMULATE_PREPARES = false`, `utf8mb4`) |
| **Segurança** | bcrypt · queries 100% parametrizadas · sessão HttpOnly/SameSite + CSRF · rate limiting · headers CSP/HSTS · TLS opcional |
| **Privacidade** | LGPD: Política/Termos, exclusão de conta por anonimização, fontes/ícones self-hosted, vídeo com consentimento · docs em `docs/privacidade/` |
| **Deploy** | Docker + Docker Compose (`php:8.2-apache`) · blueprint Render (`render.yaml`) |
| **Testes** | PHPUnit 11 |

</div>

---

## 📁 Estrutura do Projeto

```
Portal-Receitas/
├─ public/                              ← Único docroot (Apache serve só esta pasta)
│  ├─ index.php                         ← Home: listagem, busca e filtro
│  ├─ login.php · register.php · profile.php
│  ├─ healthz.php                       ← Health check (200, não toca o banco)
│  ├─ api/                              ← Endpoints JSON de auth (login, register, logout, me, delete-account)
│  ├─ privacidade.php · termos.php      ← Política de Privacidade e Termos de Uso (LGPD)
│  └─ assets/                           ← css/, js/, img/, fonts/, vendor/ (self-hosted) e php/ (logout)
│
├─ src/                                 ← Núcleo por camada (regra: Presentation → Application → Domain)
│  ├─ Domain/
│  │  ├─ Repository/                    ← UserRepositoryInterface, RecipeRepositoryInterface
│  │  └─ Exception/                     ← Domain / Validation / Authentication Exception
│  ├─ Application/UseCase/              ← AuthenticateUser · RegisterUser · UpdateUserProfile · FindRecipes
│  ├─ Infrastructure/
│  │  ├─ Database/PdoConnectionFactory.php
│  │  └─ Repository/                    ← PdoUserRepository, PdoRecipeRepository (implementam o Domain)
│  └─ Presentation/
│     ├─ Controller/                    ← Auth · Profile · Recipe
│     ├─ Http/SessionManager.php
│     └─ View/                          ← index · auth (login/cadastro) · profile · unavailable
│
├─ config/bootstrap.php                 ← Composição de dependências + leitura do ambiente
├─ tests/                               ← Unit/ (use cases + conexão PDO) e Support/ (fakes in-memory)
│
├─ DB_Receitas.sql                      ← Banco oficial (script único: DDL, views, rotinas, triggers, seed)
├─ Dockerfile · docker-compose.yml
├─ docker/                              ← Entrypoint (porta dinâmica) e imagens all-in-one/DB
├─ render.yaml                          ← Blueprint de deploy na Render (plano free)
├─ DEPLOY.md · CHANGELOG.md · docs/architecture.md
└─ composer.json · phpunit.xml
```

A regra de dependência e o papel de cada camada estão resumidos em [docs/architecture.md](docs/architecture.md). As **referências técnicas oficiais** do projeto: [docs/backend.md](docs/backend.md) (serviços, regras de negócio, API, segurança, escalabilidade, ADRs), [docs/frontend.md](docs/frontend.md) (Design System, UX/UI, acessibilidade, performance, ADRs) e [docs/auditoria-conformidade.md](docs/auditoria-conformidade.md) (auditoria LGPD + ISO/IEC 25010, com correções aplicadas na v2.2).

---

## ⚙️ Configuração (variáveis de ambiente)

O banco é configurado **inteiramente por ambiente**, lido em `config/bootstrap.php`. Sem variáveis, os padrões apontam para um XAMPP local.

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `DB_HOST` | `localhost` | Host do MySQL |
| `DB_NAME` | `tcc_receitas` | Nome do banco |
| `DB_USER` | `root` | Usuário |
| `DB_PASS` | *(vazio)* | Senha |
| `DB_PORT` | `3306` | Porta (útil em MySQL gerenciado externo) |
| `DB_SSL_CA` | *(nulo)* | Caminho do certificado CA (PEM) quando o provedor exige TLS (ex.: Aiven) |
| `DB_SSL_VERIFY` | `true` | Verificação do certificado do servidor; use `false` só para cert. autoassinado |

> `WEB_PORT` e `DB_PASS` do `.env.example` são consumidos pelo `docker-compose.yml` (porta publicada e senha do serviço MySQL).

---

## 📦 Como Rodar

### 🐳 Com Docker (recomendado)

```bash
git clone https://github.com/https-shini/Portal-Receitas.git
cd Portal-Receitas
cp .env.example .env          # opcional: ajuste WEB_PORT e DB_PASS
docker compose up --build -d
```

Acesse **http://localhost:8080**. O seed (`DB_Receitas.sql`) é importado automaticamente na **primeira** subida (volume vazio), e o serviço `web` só inicia depois do healthcheck do `db` ficar `healthy`.

### ☁️ Na Render (plano free)

O repositório traz o blueprint `render.yaml` (**New → Blueprint** no dashboard). No modo free, um único container roda Apache/PHP + MariaDB com o seed importado no boot — custo zero e health check já apontado para `/healthz.php`.

> ⚠️ O free não tem disco persistente: cadastros e edições feitos em runtime somem quando o serviço hiberna ou redeploya (receitas e usuários-demo sempre voltam pelo seed). Opções com persistência (MySQL externo ou serviços separados) estão em [DEPLOY.md](DEPLOY.md).

### 💻 Com XAMPP (desenvolvimento local)

1. Clone o projeto e rode `composer install`.
2. Importe `DB_Receitas.sql` (cria o banco `tcc_receitas`).
3. Aponte o docroot do Apache para a pasta **`public/`**.
4. Sem variáveis de ambiente, a conexão usa `localhost` / `root` / senha vazia / `tcc_receitas`.

### 🔑 Usuários de demonstração (do seed)

| E-mail | Senha |
|--------|-------|
| `demo1@example.com` | `123456` |
| `demo2@example.com` | `271821` |

---

## 🧪 Testes

```bash
composer install
composer test
```

A suíte PHPUnit cobre os casos de uso e o fluxo de conexão:

- **Cadastro** — hash bcrypt compatível, rejeição de e-mail duplicado, formato de e-mail inválido e categoria ausente.
- **Autenticação** — credenciais corretas, hash dos usuários-demo do seed, senha errada, e-mail inexistente e credenciais vazias.
- **Perfil** — atualização de nome/e-mail/senha com hash, preservação da senha quando o campo vem em branco e rejeição de ID inválido.
- **Conexão PDO** — exceção em host inalcançável e reuso da mesma conexão; o teste de integração real roda apenas quando `TEST_DB_HOST` está definido.

Os testes usam fakes in-memory (`tests/Support/`), sem dependência de banco para a maioria dos casos.

---

## 🔐 Notas de segurança

- Todas as senhas são gravadas e verificadas via bcrypt (`password_hash`/`password_verify`); a coluna `senhaUsuario` é `varchar(255)` para comportar o hash.
- **100% das queries** usam prepared statements parametrizados (`PdoUserRepository`, `PdoRecipeRepository`), com `EMULATE_PREPARES = false`.
- O acesso ao perfil exige sessão autenticada (`SessionManager` + guard no `ProfileController`); todo redirecionamento é seguido de `exit;`.

---

## 👥 Créditos

### Instituição

- **Escola:** ETEC de Vila Formosa · **Curso:** Técnico em Desenvolvimento de Sistemas (Integrado ao NOVOTEC) · **Ano:** 2022

### Equipe

<div align="center">

| Nome | Função |
|------|--------|
| Cassiano Reis de Jesus | Desenvolvedor |
| Guilherme de Souza Cruz | Desenvolvedor |
| Henrriky Jhonny de Oliveira | Desenvolvedor |
| João Vitor Santos de Matos | Desenvolvedor |
| Nicolas de Abreu Alves | Desenvolvedor |
| Rodrigo Mazucato Lopes de Souza | Desenvolvedor |
| Sabrina Maia Quirino | Desenvolvedora |

**Orientadores:** Prof. Márcio Bergamin e Prof. Sérgio Muniz

</div>

---

## 🐛 Troubleshooting

- **Conexão recusada (Docker):** aguarde o serviço `db` ficar `healthy` (`docker compose ps`); o `web` só sobe depois dele.
- **Conexão recusada (XAMPP):** confira se o MySQL está ativo e se o banco `tcc_receitas` foi importado. Para valores diferentes dos padrões, defina `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- **Seed não reimporta:** o MySQL só importa `DB_Receitas.sql` com o volume vazio. Rode `docker compose down -v` e suba de novo.
- **Página 503 (indisponível):** o banco está fora do ar — a aplicação exibe a view `unavailable.php` em vez de um erro 500 seco. Verifique o serviço de banco.
- **Página em branco:** cheque os logs (`docker compose logs -f web` ou `xampp/apache/logs/error.log`).

---

<img width=100% src="https://capsule-render.vercel.app/api?type=waving&color=gradient&height=120&section=footer" alt="Footer Wave"/>
