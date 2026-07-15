# CHANGELOG — Consolidação das branches

## [1.1.0] — 2026-07-15

Preparação para hospedagem na Render (e plataformas similares que injetam `PORT`):

- Apache passa a respeitar a variável `PORT` via `docker/apache-entrypoint.sh` (padrão continua 80 no compose local).
- `render.yaml` (blueprint): serviço web Docker + serviço privado MySQL 8 com disco e seed automático (`docker/db.Dockerfile`); senha do banco gerada pela Render e injetada como `DB_PASS`.
- `DB_PORT` configurável na conexão (padrão `3306`) — necessário para MySQL externo no plano free.
- Health check leve em `public/healthz.php` (sem tocar o banco) para o `healthCheckPath` da Render.
- `DEPLOY.md` com o passo a passo das duas opções de deploy na Render; `README.md` atualizado.

## [1.0.0] — 2026-07-15

Versão definitiva única, combinando o melhor de cada branch do repositório.

### Adotado da `copilot/refatoracao-arquitetural-completa` (arquitetura-alvo)
- Clean Architecture em `src/` (Domain / Application / Infrastructure / Presentation) com autoload PSR-4 (`App\ → src/`).
- `public/` como único docroot (Dockerfile reaponta o Apache).
- `config/bootstrap.php` com composição de dependências e configuração do banco por variáveis de ambiente (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) com fallback para XAMPP (`localhost`/`root`/senha vazia/`tcc_receitas`).
- `SessionManager` como mecanismo único de sessão (chave `logado`), guard de autenticação no perfil e `exit;` após todo `header('Location: ...')`.

### Adotado da `copilot/auditoria-projeto-refatorado` (correções funcionais)
- `senhaUsuario varchar(255)` em `DB_Receitas.sql` e `ReceitasAntigo.sql` — corrige o erro MySQL 1406 ("Data too long") no cadastro, já que o hash bcrypt não cabia em `varchar(20)`.
- Usuários-demo do seed re-semeados com hash bcrypt compatível com `password_verify` (senhas em claro documentadas em comentário no SQL: `123456` e `271821`).
- `.env.example` (`WEB_PORT`, `DB_PASS`).

### Adotado da `main` (deploy e segurança já funcionais)
- `docker-compose.yml` com web + MySQL 8, healthcheck e import automático do seed em banco vazio.
- 100% das senhas com `password_hash`/`password_verify` e 100% das queries com prepared statements parametrizados (mantidos nas camadas novas).

### Novo nesta consolidação
- Dockerfile roda `composer install --no-dev --optimize-autoloader` no build — a imagem sempre tem `vendor/autoload.php` (bug da branch de refatoração, que não instalava o autoloader e não subia).
- `vendor/` no `.gitignore`; `composer.lock` versionado para build reproduzível.
- Testes PHPUnit reais em `tests/` (use cases de cadastro, autenticação e perfil + fluxo de conexão PDO); `composer test`.
- `README.md` alinhado ao que o projeto realmente faz; funcionalidades inexistentes movidas para a seção Roadmap.
- `DEPLOY.md` atualizado (variáveis `WEB_PORT`/`DB_PASS`, `down -v` para reimportar o seed).
- Correção no fluxo de cadastro: `header('Refresh...')` enviado antes de qualquer output.

### Removido
- Entrypoints PHP duplicados na raiz (`index.php`, `login.php`, `register.php`, `profile.php`) — substituídos pelos de `public/`.
- Controllers legados `assets/php/*.php` (`conexao.php`, `usuarioController.php`, `receitaController.php`, `addusuario.php`, `emailExists.php`, `logout.php`) — substituídos pelas camadas em `src/`.
- Alias do Apache para `assets/` fora do docroot — os assets estáticos (css/js/img) foram movidos para `public/assets/`.
- Diretórios vazios de scaffolding (`database/`, `docker/`) e o `tests/.gitkeep`.
- Do `README.md`: descrição de funcionalidades inexistentes (engine de recomendação, calculadora de calorias em tempo real, favoritos, avaliações, comunidade) — agora marcadas como roadmap; referências a arquivos inexistentes (`config.php`, `auth.php`, `recipes.php`).
