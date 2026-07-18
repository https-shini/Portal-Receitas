# Plano de Ação — Refatoração “Marketplace” (HomeMadeGourmet)

> Documento de planejamento aprovado que orienta a refatoração da home em uma
> experiência de catálogo estilo marketplace (referências: Mercado Livre,
> Amazon, Shopee), com filtros dinâmicos e páginas individuais reutilizáveis.
> A implementação é feita em etapas (E1–E7), cada uma em um commit separado.

## Decisões de escopo aprovadas

| Decisão | Escolha |
|---|---|
| URL das receitas | **URL amigável** `/receita/{id}/{slug}` (habilitar `mod_rewrite` + `.htaccess`) |
| Novos campos | **Núcleo agora** (dificuldade + tempo de cozimento + dicas); galeria e nutrição detalhada em fase posterior |
| Avaliação / Favoritos | **Favoritos agora**; avaliação por estrelas em fase posterior |
| Filtros | **Multi-facetado + ordenação** (categorias múltiplas + dificuldade + ordenar por) |

---

## 1. Objetivos da refatoração

1. Transformar a home de **lista + modal** em uma **vitrine de catálogo** estilo marketplace (cards ricos, filtros facetados, ordenação).
2. **Eliminar o pop-up/modal** e dar a cada receita uma **página própria** (`/receita/{id}/{slug}`) com **layout único reutilizável** — padrão de “página de produto”.
3. Tornar as **categorias controladas por dados** (tabela `categoria`), removendo as três cópias hardcoded atuais.
4. Introduzir **Favoritar** para usuários autenticados (persistente).
5. Preparar base de dados e UI para **dificuldade, tempo de cozimento, dicas, avaliação, galeria e relacionadas** — núcleo agora, resto pronto para as próximas fases.
6. Preservar os pontos fortes: LGPD (consentimento de vídeo), acessibilidade WCAG 2.2 AA, temas claro/escuro, Design System e Clean Architecture.

## 2. Levantamento das alterações necessárias

Estado atual confirmado lendo o código:

- **Rota:** um arquivo PHP por rota, servido direto pelo Apache. Não há `.htaccess` nem `mod_rewrite` (só `mod_headers`). Estado por querystring.
- **Dados:** `RecipeRepositoryInterface` é somente leitura, com `findSummaries()` e `findDetails()` — **não existe `findById()`**. O modal casa a receita no cliente a partir de um _dump_ de todos os detalhes.
- **Home:** emite todos os 36 detalhes como `<template>` inertes + 36 iframes → payload pesado.
- **Categorias:** hardcoded em 3 lugares (`FindRecipesUseCase::CATEGORY_LABELS`, `$categorias` na view, e a tabela `categoria` — semeada mas cujos nomes nem são lidos em runtime).
- **Não existem no banco:** dificuldade, nota/avaliação, tempo de cozimento separado, nutrição além de `qtdCalorias`, dicas, galeria (só `imagem`), relacionadas.
- **Não existe** favoritos nem avaliação. A “categoria favorita” do usuário é outra coisa (preferência de categoria).
- **API** (`public/api/*`) é só de autenticação; receitas são server-rendered.

Lacunas: rota amigável + página dedicada; `findById`/`findRelated`; categorias data-driven; colunas novas (dificuldade, tempoCozimento, dicas); tabela e fluxo de favoritos; filtros facetados + ordenação + paginação; card como componente reutilizável; remoção do modal.

## 3. Arquitetura proposta

Mantém a Clean Architecture e o padrão _one-file-per-route_, estendendo cada camada.

**Infra de rota**
- `a2enmod rewrite` + `AllowOverride All` no `Dockerfile` e no `docker/render-free.Dockerfile`.
- Novo `public/.htaccess`: reescreve `/receita/{id}/{slug}` → `receita.php?id={id}` e `/receitas` → `index.php`; fallback query-param preservado.
- Novo entrypoint `public/receita.php` (valida id → controller → view; id inválido → `View/not-found.php`, HTTP 404).

**Domínio / Aplicação**
- `RecipeRepositoryInterface`: **+`findById(int): ?array`**, **+`findRelated($categoryId, $excludeId, $limit)`**; `findSummaries()` passa a receber um objeto de critério `RecipeQuery` (busca, `categoryIds[]`, `difficulties[]`, `sort`, `page`, `perPage`).
- Novo `CategoryRepositoryInterface` + `PdoCategoryRepository::findAllWithCounts()` → fonte única de categorias.
- Novo `FavoriteRepositoryInterface` + `PdoFavoriteRepository` (`add`, `remove`, `existe`, `idsByUser`, `listByUser`).
- Novos use cases: `ShowRecipeUseCase`, `ListCategoriesUseCase`, `ToggleFavoriteUseCase`, `ListFavoritesUseCase`. `FindRecipesUseCase` refatorado para receber `RecipeQuery` e devolver só summaries + metadados de paginação.

**Apresentação**
- `RecipeController`: `list()` monta `RecipeQuery`; **+`show(int $id)`**. Novo `FavoriteController` (API).
- Novo `public/api/favorites.php` (POST toggle / GET lista) com auth + CSRF, respostas `{"detail": …}`.
- DI em `config/bootstrap.php`.

**Banco (migração aditiva, idempotente no `DB_Receitas.sql`)**
- `receita`: `+dificuldade ENUM('Fácil','Médio','Difícil') NULL`, `+tempoCozimento VARCHAR(10) NULL`, `+dicas TEXT NULL` (backfill das 36 receitas no seed).
- `categoria`: `+icone VARCHAR(40) NULL`; ampliar o seed com as categorias de referência.
- Nova tabela `favorito(idUsuario FK, idReceita FK, criadoEm, PK composta, ON DELETE CASCADE)`.
- DCL: conceder apenas `SELECT, INSERT, DELETE` em `favorito` ao papel da aplicação (menor privilégio).
- Índices: `receita(idcategoriaFK)`, `receita(dificuldade)`, `favorito(idUsuario)`.
- Reservado p/ fase 2: `avaliacao`, `receita_imagem` (galeria), colunas de nutrição.

## 4. Fluxo de navegação do usuário

```
Home / Catálogo (/receitas)
 ├─ Busca + botão "Filtros" → painel (sidebar desktop / bottom-sheet mobile)
 │     categorias (múltiplas) · dificuldade · ordenar por → URL com querystring (compartilhável)
 ├─ Chips de filtros ativos (removíveis) + contagem de resultados
 ├─ Grade de cards → clique no card
 └─ Paginação "Carregar mais" / ?page=N
          ▼
Página da Receita (/receita/{id}/{slug})
 ├─ Breadcrumb (Início / Categoria / Receita)
 ├─ Hero (imagem) + título + selos (categoria, preparo, cozimento, porções, dificuldade, calorias, [avaliação])
 ├─ Ações: ♥ Favoritar (logado; senão → login) · Compartilhar
 ├─ Ingredientes | Modo de preparo (2 col desktop / empilhado mobile)
 ├─ Dicas (se houver) · Vídeo (consentimento LGPD por clique)
 └─ Receitas relacionadas (mesma categoria) → volta ao fluxo

Favoritas (/favoritas, só logado) → grade reutilizando o mesmo card
```

## 5. Lista de componentes a criar e a modificar

**Criar**

| Item | Caminho |
|---|---|
| Partial de card reutilizável | `src/Presentation/View/partials/recipe-card.php` |
| Painel de filtros | `partials/filters.php` |
| View página de receita (layout único) | `src/Presentation/View/recipe.php` |
| View favoritas / 404 | `View/favorites.php` · `View/not-found.php` |
| Entrypoints | `public/receita.php` · `public/favoritas.php` |
| JS catálogo / receita | `assets/js/catalog.js` · `assets/js/recipe.js` |
| CSS | `pages/catalog.css` · `pages/recipe.css` |
| Repos / UseCases / Controllers / DTO | Category/Favorite Repository · ShowRecipe/ToggleFavorite/ListCategories/ListFavorites · FavoriteController · `RecipeQuery` |
| API favoritos | `public/api/favorites.php` |
| Reescrita de URL | `public/.htaccess` |

**Modificar**

| Item | Mudança |
|---|---|
| `View/index.php` | Vira catálogo: remove modal/`<template>`s, usa `recipe-card.php` + `filters.php` + paginação |
| `assets/js/home.js` | Remove código do modal; mantém skeleton (ou migra p/ `catalog.js`) |
| `FindRecipesUseCase` | Recebe `RecipeQuery`; retorna summaries + paginação; remove `CATEGORY_LABELS` |
| `RecipeController` | Monta critério em `list()`; +`show()` |
| `RecipeRepositoryInterface` + `PdoRecipeRepository` | +`findById`, +`findRelated`, filtros facetados/ordenação/paginação |
| `partials/header.php` | Link “Favoritas” quando logado |
| `config/bootstrap.php` | Wiring dos novos serviços |
| `DB_Receitas.sql` | Colunas / tabela / seed / grants / índices |
| `Dockerfile` + `render-free.Dockerfile` | `a2enmod rewrite` + `AllowOverride All` |
| `sitemap.xml` / geração | Uma URL por receita + canonical + JSON-LD `Recipe` por página |

**Reutilização (DS existente):** `.card`, `.badge`, `.btn(--primary/ghost/soft/icon/danger)`, `.chip`, `.field`, `.empty`, `.skeleton(-card)`, `.alert`, partials `head/header/footer`, tokens (grid 8pt, `--radius-*`, `--color-primary #C2410C`, breakpoints 40/48/56rem, grid `auto-fill minmax(15rem,1fr)`). O consentimento de vídeo (`.video-consent`) migra do modal para `recipe.php`.

## 6. Estrutura das novas páginas

- **Catálogo (`/receitas`)** — search bar + botão “Filtros”; painel facetado (categorias multi, dificuldade, ordenar por relevância/tempo/nome/avaliação); barra de filtros ativos (chips removíveis) + contagem; grade responsiva de cards; estados de skeleton, vazio e “sem resultados nesta combinação”; paginação “Carregar mais”.
- **Card** — foto, nome, categoria, tempo de preparo, dificuldade, avaliação (placeholder “Sem avaliações” até fase 2) + tempo de cozimento/porções como complementares. `<a href="/receita/{id}/{slug}">` (sem botão de modal).
- **Página da Receita (`/receita/{id}/{slug}`)** — layout único data-driven: breadcrumb; imagem principal (galeria preparada p/ fase 2); título; selos; ações Favoritar + Compartilhar; ingredientes; modo de preparo; dicas (condicional); vídeo com consentimento; relacionadas; JSON-LD `Recipe` completo.
- **Favoritas (`/favoritas`, logado)** — mesma grade/card; estado vazio com CTA para o catálogo.

## 7. Impactos no projeto

- **Código:** mudança concentrada em Presentation + novos Application/Infrastructure; contratos de repositório evoluem de forma aditiva. Remoção do modal simplifica `home.js`.
- **Dados:** migração aditiva (colunas NULL + tabela nova) sem quebrar dados; backfill das 36 receitas no seed; categorias lidas do banco; grants ampliados só para `favorito`. Render free é efêmero → tudo idempotente no seed.
- **Desempenho:** home deixa de emitir 36 detalhes + 36 `<template>` + `findDetails` → HTML muito menor, menos iframes. Página de receita: `findById` + `findRelated(LIMIT 6)`. Filtros por _bound params_ + `ORDER BY` por whitelist. Cache APCu passa a valer mais. Paginação (12/página).

## 8. Ordem recomendada de implementação

1. **E1 — Infra de rota:** `mod_rewrite` + `.htaccess` + `receita.php` (stub) + `not-found.php`.
2. **E2 — Camada de leitura:** `findById`, `findRelated`, `CategoryRepository` (categorias data-driven); remover hardcode.
3. **E3 — Página de receita:** `recipe.php` migrando o conteúdo do modal + consentimento de vídeo; card vira `<a>`; remover o modal.
4. **E4 — Catálogo + filtros facetados + paginação:** `filters.php`, `recipe-card.php`, `catalog.js/css`, cards enriquecidos.
5. **E5 — Migração de banco:** dificuldade/tempoCozimento/dicas + backfill; refletir no card/página.
6. **E6 — Favoritos:** tabela + repo + use cases + API + botão + `/favoritas` + grants.
7. **E7 — Qualidade:** PHPUnit, E2E, SEO (JSON-LD por página + sitemap), a11y, screenshots.
8. **Fase 2 (fora deste ciclo):** avaliação por usuários, galeria, nutrição detalhada.

## 9. Cronograma técnico por etapas

| Etapa | Escopo | Tamanho | ~Dias |
|---|---|---|---|
| E1 | Infra de rota + 404 + stub | P | 0,5 |
| E2 | Leitura + categorias data-driven | M | 1,5 |
| E3 | Página de receita + remoção do modal | M | 2 |
| E4 | Catálogo + filtros + paginação + card partial | G | 3 |
| E5 | Migração DB + backfill | M | 1,5 |
| E6 | Favoritos (DB → API → UI → página) | G | 2,5 |
| E7 | Testes, SEO, a11y, docs | M | 1,5 |
| **Total fase 1** | | | **~12,5 dias** |
| Fase 2 | Avaliação + galeria + nutrição | G | ciclo à parte |

## 10. Riscos e estratégias de mitigação

| Risco | Mitigação |
|---|---|
| `mod_rewrite`/`AllowOverride` indisponível no ambiente | Manter fallback query-param funcional; testar reescrita no container; canonical na página |
| Remoção do modal quebra fluxo/links | Card → página com `<a>` real; E2E; 404 amigável |
| Categorias hardcoded em 3 fontes | Migrar rótulos/ícones para `categoria`; tratar `idcategoriaFK` nulo como “Sem categoria” |
| Receitas sem os campos novos | Colunas NULL + backfill no seed; UI oculta campo ausente (degradação graciosa) |
| Favoritos exige `INSERT/DELETE` | Grant restrito apenas à tabela `favorito`; auth + CSRF + rate limit na API |
| Ambiente efêmero (reseed) apaga favoritos | Documentar como esperado no free tier; persistência real só com disco |
| Injeção via ordenação/filtros | `ORDER BY`/colunas por whitelist; categorias/dificuldade por _bound params_ `IN()` |
| SEO: muitas URLs novas | Sitemap dinâmico + canonical + JSON-LD `Recipe` por página |
| Escopo × prazo do TCC | Faseamento: fase 1 entrega marketplace + favoritos; avaliação/galeria/nutrição na fase 2 |

## Anexo · Recomendações de UX/UI

- **Navegação:** header fixo mantido; breadcrumb na página de receita; botão “Filtros” revela painel; filtros ativos como chips removíveis; contagem de resultados em `aria-live`.
- **Hierarquia visual:** catálogo com busca + filtros no topo, contagem, grade; receita com hero + título + fatos-chave + ações acima da dobra; ingredientes/preparo 2 colunas no desktop; relacionadas ao fim.
- **Espaçamento & responsividade:** grid de 8pt; grade `auto-fill minmax(15rem,1fr)`; painel como sidebar ≥ 48rem, bottom-sheet < 48rem.
- **Carregamento:** reuso de `.skeleton-card`; feedback no botão de busca (`aria-busy`).
- **Sem resultados:** reuso de `.empty` com CTA claro para limpar filtros.
- **Paginação vs. scroll infinito:** **recomendado paginação** (“Carregar mais” como _progressive enhancement_ sobre `?page=N` real) — melhor para SEO, acessibilidade (rodapé alcançável, botão voltar) e desempenho. Scroll infinito descartado por prender o rodapé e prejudicar a11y/SEO.
