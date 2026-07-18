# CHANGELOG — Consolidação das branches

## [2.4.0] — 2026-07-18

Melhorias do guia técnico (`docs/GUIA-DE-MELHORIAS.md`) e refatoração final de header/footer, sem regressões (24 testes verdes):

- **Header/Footer em camada global própria:** estilos movidos de `pages/home.css` para `public/assets/css/layout.css` (carregado em todas as páginas via `head.php`) e comportamento em `public/assets/js/header.js` (incluído no `footer.php`, ativo em toda página) — antes o JS do menu do usuário só rodava na home. Header ganha estado de rolagem (`.is-scrolled`) e o menu do usuário fecha ao clicar fora/Esc em qualquer página.
- **Rodapé reformulado:** três colunas (marca + tagline · navegação com ícones · contato com CTA "Fale conosco", e-mail e localização), divisor em gradiente e barra inferior com copyright dinâmico.
- **SEO — dados estruturados `Recipe` (JSON-LD):** `index.php` emite um `ItemList` de `Recipe` com os campos já visíveis no card (nome, imagem, categoria); barra de escape de `/` para evitar quebra da tag.
- **Cache-Control de assets estáticos** (`docker/security-headers.conf`): 1h para CSS/JS, 30 dias `immutable` para imagens/fontes/ícones — só `mod_headers`, sem tocar nos Dockerfiles.
- **`composer audit` no CI** (`.github/workflows/ci.yml`): falha o build diante de dependência com vulnerabilidade conhecida.
- **Contato oficial do projeto** atualizado para `receitasdelicia498@gmail.com` nos documentos públicos (privacidade, termos, registro de tratamento, runbook) e no rodapé.
- `pages/home.css` removido dos `pageCss` de auth/perfil/privacidade/termos (o layout global já vem de `layout.css`).
- `docs/GUIA-DE-MELHORIAS.md`: guia técnico priorizado (o que já está pronto, alta/média/contínua) para a evolução do projeto.

## [2.3.0] — 2026-07-17

Refatoração das telas de Login e Cadastro e do layout global, sem regressões (24 testes verdes):

- **Header e Footer globais:** extraídos para partials reutilizáveis (`src/Presentation/View/partials/head.php`, `header.php`, `footer.php`) e aplicados em **todas** as páginas (home, login, cadastro, perfil, privacidade, termos) — antes as telas de autenticação não tinham header/footer.
- **Login e Cadastro independentes:** eliminado o card duplo deslizante que causava **sobreposição** entre as telas. Agora são páginas separadas de card único (`login.php` ⇄ `register.php`), sem conflitos de posicionamento nem elementos sobrepostos.
- **Fluxo de cadastro:** ao concluir o cadastro, o usuário é redirecionado para `login.php?cadastro=ok`, que exibe banner de sucesso — sem alternância por JavaScript.
- **Catálogo público reforçado:** o header reflete o estado de autenticação — botão **"Entrar"** para visitantes e menu do usuário (Meu perfil / Sair) para autenticados; as receitas continuam acessíveis sem login.
- **Responsividade e temas:** ambas as telas validadas em desktop/mobile e claro/escuro, com navegação "Receitas" no header e links (Receitas · Privacidade · Termos) no footer.

## [2.2.0] — 2026-07-17

Correção das não conformidades da auditoria (docs/auditoria-conformidade.md), sem regressões:

- **NC-01 (Política/Termos):** páginas `privacidade.php` e `termos.php` versionadas e linkadas nos rodapés; checkbox de aceite obrigatório no cadastro (versão em `AuthController::LEGAL_VERSION`).
- **NC-02 (Exclusão de conta — art. 18, VI):** fluxo completo na área do usuário com reautenticação por senha e confirmação; `DeleteUserAccountUseCase` por **anonimização irreversível** (preserva o menor privilégio do banco — a aplicação não recebe DELETE); auditada por trigger.
- **NC-03 (Terceiros):** fontes (Inter/Sora) e ícones (Line Awesome) **self-hosted** — zero requisições a Google/Icons8; vídeos por `youtube-nocookie.com` com **consentimento por clique** (o iframe só carrega após ação do usuário).
- **NC-04 (Seeds):** usuários-demo com dados fictícios (`demo1@example.com`, `demo2@example.com` — RFC 2606).
- **Segurança:** rate limiting (login 5/min, cadastro 10/min → 429), token **CSRF** no perfil, `password_needs_rehash` no login, cabeçalhos **CSP/HSTS/X-Frame-Options/X-Content-Type-Options/Referrer-Policy/Permissions-Policy** (Apache).
- **Governança:** pipeline **CI** (GitHub Actions: lint PHP/JS + PHPUnit + build das imagens); documentos de privacidade em `docs/privacidade/` (registro de tratamento, LIA, DPIA simplificado, política de retenção com `sp_expurgar_auditoria`, runbook de incidentes).
- **Testes:** 24 (novos: exclusão por anonimização, rate limiter).
- Coluna `receita.link` ampliada para `varchar(400)` (acomoda o domínio youtube-nocookie).

## [2.1.0] — 2026-07-15

- `docs/auditoria-conformidade.md`: auditoria técnica oficial de conformidade com a LGPD e a ISO/IEC 25010 — inventário de dados pessoais com ciclo de vida, bases legais recomendadas, avaliação dos direitos do titular, segurança (art. 46) evidência por evidência, notas por característica ISO (média 3,9/5), 6 não conformidades e 8 riscos classificados em matriz, plano de adequação em 3 ondas, roadmaps, checklists, KPIs e critérios objetivos de aceitação.
- `docs/frontend.md`: documento técnico oficial da camada de apresentação — princípios, arquitetura em camadas do CSS/JS com diagramas, estrutura de diretórios, Design System (tokens, componentes, motion), temas, responsividade mobile-first, UX aplicada (Hick/Fitts/Nielsen/Gestalt com exemplos do produto), acessibilidade WCAG 2.2 AA, navegação MPA justificada, inventário de estado e fluxo de dados, integração com a API, validação em dupla camada, segurança do front, performance (lazy de iframes/imagens, code splitting natural), SEO/PWA, testes, compatibilidade de navegadores, 6 ADRs e roadmap priorizado.
- `docs/backend.md`: documento técnico oficial da camada backend — requisitos funcionais/não funcionais, arquitetura em camadas com diagramas (componentes e sequência), estrutura de módulos, regras de negócio e onde vivem, SOLID/patterns aplicados, contrato da API (+decisões sobre versionamento, OpenAPI e GraphQL), autenticação/RBAC, mapeamento OWASP, erros/logs/auditoria, observabilidade, dados (cache/filas quando aplicável), escalabilidade e tolerância a falhas, estratégia de testes, containers/CI-CD, configuração por ambiente, governança, 7 ADRs e roadmap técnico priorizado.
- `docs/architecture.md` e `README.md` passam a apontar para a nova referência.

## [2.0.0] — 2026-07-15

Frontend integralmente refatorado com Design System próprio (conceito Liquid Glass), preservando 100% das funcionalidades:

- **Design System** em `public/assets/css/`: `tokens.css` (paleta completa light/dark com primary/secondary/accent/feedback/superfícies/texto/estados, espaçamento em 8pt grid, escala tipográfica Inter+Sora, raios, 4 níveis de elevação, blur de glass, durações/easings), `base.css` (reset, hierarquia H1-H6, foco visível, skip link, `prefers-reduced-motion`) e `components.css` (botões com hover/focus/active/disabled/loading, campos, chips, cards, badges, alerts, modal, skeleton, estados vazios).
- **Tema claro/escuro** com toggle persistido (`localStorage`) e detecção de `prefers-color-scheme`, aplicado antes do paint (sem FOUC).
- **Home reprojetada**: header glass fixo com menu do usuário (details nativo), hero de busca, chips de categoria, grid responsivo de cards, estados vazios desenhados, skeleton de feedback ao buscar e contagem de resultados.
- **Modal de receita acessível** (`role="dialog"`, Esc, clique no backdrop, foco gerenciado e devolvido): o conteúdo vem de `<template>` inerte — **os 36 iframes do YouTube saíram do carregamento inicial** e o vídeo só carrega ao abrir a receita (e para ao fechar).
- **Acessibilidade (WCAG 2.2 AA)**: landmarks (`header/main/footer` + roles), skip link, navegação 100% por teclado (cards são botões), labels/ARIA em formulários e alertas `aria-live`, contraste recalculado na paleta.
- **SEO**: HTML semântico, meta description, Open Graph, Twitter Card, `theme-color`, favicon, JSON-LD (WebSite), `robots.txt` e `sitemap.xml`.
- **Telas de acesso e perfil** reconstruídas no DS (mesmos fluxos, IDs e API); perfil ganhou prevenção de envio acidental (Salvar só habilita após Editar) e mensagens de erro claras inline.
- Página de indisponibilidade autocontida redesenhada (dark/light automático).
- Removidos os CSS/JS antigos por página (`index.css`, `auth.css`, `profile.css`, `script-index.js`, `script-profile.js`).

## [1.4.0] — 2026-07-15

Banco de dados único e oficial — `ReceitasAntigo.sql` removido definitivamente:

- `DB_Receitas.sql` reescrito como script único, autocontido e idempotente, organizado em 17 seções comentadas (configuração, drops, criação do banco, tabelas, constraints, índices, views, functions, procedures, triggers, seed, consultas de exemplo, DCL, TCL e testes).
- DDL modernizado mantendo o contrato com a aplicação: constraints nomeadas (`pk_/fk_/uq_/ck_`), CHECKs (formato de e-mail, senha sempre em hash ≥60 chars, porções > 0, calorias ≥ 0), FKs com `ON DELETE SET NULL`/`ON UPDATE CASCADE`, timestamps de criação/atualização e índice FULLTEXT nos ingredientes.
- Views (`vw_receita_card`, `vw_estatisticas_categoria`, `vw_usuario_publico` — atualizável e sem coluna de senha), functions (`fn_calorias_por_porcao`, `fn_total_receitas_categoria`) e procedures (`sp_buscar_receitas_por_ingrediente`, `sp_relatorio_categorias` com cursor/loop/handler, `sp_trocar_categoria_favorita` com transação e RESIGNAL).
- Triggers de validação e auditoria (`auditoria_usuario` registra INSERT/UPDATE/DELETE de contas sem expor senhas).
- DCL com princípio do menor privilégio: papéis `papel_leitura`/`papel_aplicacao`, usuários `portal_app`/`portal_relatorios`, exemplos de GRANT/REVOKE.
- TCL: transações com COMMIT/ROLLBACK/SAVEPOINT, níveis de isolamento e bloqueio `FOR UPDATE`.
- Seção final de autoteste: o import valida volumetria, integridade, hash das senhas, view atualizável, rotinas e auditoria a cada implantação.
- Compatível e testado nos dois motores do deploy: MySQL 8 (compose) e MariaDB 10.11 (Render free).

## [1.3.0] — 2026-07-15

Login e Cadastro reconstruídos com o AuthService (https://github.com/https-shini/AuthService) como referência de UX e arquitetura de autenticação, mantendo a identidade visual HomeMadeGourmet:

- Tela única de acesso (`src/Presentation/View/auth.php`) com card duplo e painel deslizante login ⇄ cadastro, medidor de força de senha em 5 níveis, alertas inline, toggle de visibilidade, loading nos botões e Enter para enviar — servida por `login.php` e `register.php` (que redirecionam para a home se já autenticado).
- API JSON de autenticação em `public/api/` (`register`, `login`, `logout`, `me`) no padrão de respostas do AuthService (`{"detail": ...}`, 400/401/503); frontend consome via `fetch`.
- Política de senha (`PasswordPolicy`): mínimo 8 caracteres com pelo menos uma letra e um número — aplicada no cadastro e na troca de senha do perfil.
- Sessão endurecida: cookie `HttpOnly` + `SameSite=Lax` (+`Secure` atrás de HTTPS), `session_regenerate_id` após login (anti fixation).
- Logging de tentativas de cadastro/login com sanitização anti log-injection.
- Removidos: views e assets antigos de login/cadastro (`script-login/register/radio.js`, `login.css`, `register.css`, `emailExists.php`).
- Testes novos: `PasswordPolicyTest` + casos de senha fraca (20 testes no total).

## [1.2.0] — 2026-07-15

Deploy 100% gratuito na Render, tudo em Docker:

- `docker/render-free.Dockerfile` + `docker/render-free-entrypoint.sh`: imagem all-in-one (Apache/PHP + MariaDB no mesmo container) com seed importado automaticamente no boot — para o plano free da Render, que não tem disco persistente (dados de runtime são efêmeros; receitas e demos sempre voltam pelo seed).
- `render.yaml` reescrito para o modo free (um único serviço web, custo zero).
- Suporte opcional a TLS na conexão MySQL (`DB_SSL_CA`, `DB_SSL_VERIFY`) para a alternativa com MySQL externo gerenciado.
- Página amigável de indisponibilidade (HTTP 503) quando o banco está fora, em vez de erro 500 seco, nos quatro entrypoints que tocam o banco.
- `DEPLOY.md` reorganizado: opção A free all-in-one (padrão), opção B free com MySQL externo persistente, opção C paga com serviços separados.

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
