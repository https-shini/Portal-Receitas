<?php
/** @var array $viewData */
$cards = $viewData['cards'] ?? [];
$categories = $viewData['categories'] ?? [];
$filters = $viewData['filters'] ?? ['search' => null, 'categoryIds' => [], 'sort' => 'relevancia'];
$pagination = $viewData['pagination'] ?? ['page' => 1, 'perPage' => 12, 'total' => count($cards), 'totalPages' => 1, 'hasMore' => false];
$errorMessage = $viewData['errorMessage'] ?? null;

$pesquisaAtual = (string) ($filters['search'] ?? '');
$filtrando = ($filters['search'] ?? null) !== null || ($filters['categoryIds'] ?? []) !== [];

// Mapa id → nome para os chips de filtros ativos.
$nomePorCategoria = [];
foreach ($categories as $categoria) {
    $nomePorCategoria[$categoria['id']] = $categoria['name'];
}

/*
 * Monta uma URL do catálogo preservando os filtros atuais, com sobrescritas
 * pontuais (remover uma categoria, mudar de página, limpar a busca).
 */
$catalogoUrl = static function (array $overrides = []) use ($filters): string {
    $params = [];
    $search = array_key_exists('search', $overrides) ? $overrides['search'] : ($filters['search'] ?? null);
    if ($search !== null && $search !== '') {
        $params['pesquisa'] = $search;
    }
    $cats = array_key_exists('categoryIds', $overrides) ? $overrides['categoryIds'] : ($filters['categoryIds'] ?? []);
    if ($cats !== []) {
        $params['categoriaReceita'] = array_values($cats);
    }
    $sort = array_key_exists('sort', $overrides) ? $overrides['sort'] : ($filters['sort'] ?? 'relevancia');
    if ($sort !== 'relevancia') {
        $params['ordenar'] = $sort;
    }
    $page = $overrides['page'] ?? 1;
    if ($page > 1) {
        $params['pagina'] = $page;
    }
    $qs = http_build_query($params);

    return 'index.php' . ($qs !== '' ? '?' . $qs : '');
};

/*
 * Dados estruturados (schema.org/Recipe) das receitas exibidas nesta
 * página, para rich results de busca (SEO). Usa só os campos já
 * renderizados no card (nome, imagem, categoria); o passo a passo completo
 * é descrito na página dedicada de cada receita.
 */
$recipesJsonLd = '';
if (!empty($cards)) {
    $itensLista = [];
    foreach ($cards as $i => $recipe) {
        $itensLista[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'Recipe',
                'name' => $recipe['name'],
                'image' => './assets/img/' . $recipe['image'],
                'recipeCategory' => $recipe['category'],
            ],
        ];
    }
    // Sem JSON_UNESCAPED_SLASHES de propósito: "/" escapado evita que um
    // nome de receita com "</script>" quebre para fora da tag.
    $recipesJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => $itensLista,
    ], JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php
$pageTitle = 'HomeMadeGourmet — Receitas caseiras para o seu gosto';
$pageDescription = 'Portal de receitas caseiras: busque por ingrediente, filtre por categoria e siga o passo a passo com vídeo. Acesso livre, sem login.';
$pageCss = ['pages/home.css', 'pages/catalog.css'];
$extraHead = <<<'HTML'
    <meta property="og:type" content="website">
    <meta property="og:title" content="HomeMadeGourmet — Receitas caseiras para o seu gosto">
    <meta property="og:description" content="Busque receitas por ingrediente, filtre por categoria e cozinhe com vídeo passo a passo.">
    <meta property="og:image" content="./assets/img/Logo.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="HomeMadeGourmet">
    <meta name="twitter:description" content="Receitas caseiras com busca por ingrediente e vídeo passo a passo.">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebSite","name":"HomeMadeGourmet","description":"Portal de receitas caseiras com busca por ingrediente e filtro por categoria.","inLanguage":"pt-BR"}
    </script>
HTML;
if ($recipesJsonLd !== '') {
    $extraHead .= "\n    <script type=\"application/ld+json\">{$recipesJsonLd}</script>\n";
}
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <main id="conteudo" role="main">
        <!-- ── Busca e filtros (marketplace) ── -->
        <section class="search-hero container" aria-labelledby="tituloBusca">
            <h1 id="tituloBusca">O que vamos cozinhar hoje?</h1>
            <p>Busque por ingrediente ou use os filtros para explorar o catálogo.</p>

            <form action="index.php" method="GET" id="formSearch" role="search" aria-label="Buscar e filtrar receitas">
                <div class="search-bar glass">
                    <div class="field__control">
                        <i class="las la-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="campoPesquisa">Ingrediente da receita</label>
                        <input class="field__input" id="campoPesquisa" name="pesquisa" type="search"
                               placeholder="Ex.: bacon, leite, chocolate…"
                               value="<?= htmlspecialchars($pesquisaAtual) ?>">
                    </div>
                    <button class="btn btn--soft btn--filtros" type="button" id="btnFiltros"
                            aria-expanded="false" aria-controls="filtersPanel">
                        <i class="las la-sliders-h" aria-hidden="true"></i> Filtros
                        <?php if ($filters['categoryIds'] !== []): ?>
                            <span class="btn__badge"><?= count($filters['categoryIds']) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="btn btn--primary" type="submit" id="btnBuscar">
                        Buscar<span class="visually-hidden"> receitas</span>
                    </button>
                </div>

                <?php require __DIR__ . '/partials/filters.php'; ?>
            </form>
        </section>

        <!-- ── Resultados ── -->
        <section class="results container" aria-labelledby="tituloResultados">
            <div class="results__head">
                <h2 id="tituloResultados"><?= $filtrando ? 'Sua busca' : 'Receitas' ?></h2>
                <span class="results__count" role="status"><?= (int) $pagination['total'] ?> receita<?= (int) $pagination['total'] === 1 ? '' : 's' ?></span>
                <?php if ($filtrando): ?>
                    <a class="results__clear" href="index.php"><i class="las la-times" aria-hidden="true"></i> Limpar filtros</a>
                <?php endif; ?>
            </div>

            <?php if ($filtrando): ?>
                <ul class="active-filters" aria-label="Filtros ativos">
                    <?php if (($filters['search'] ?? null) !== null): ?>
                        <li>
                            <a class="active-filter" href="<?= htmlspecialchars($catalogoUrl(['search' => null])) ?>">
                                “<?= htmlspecialchars((string) $filters['search']) ?>” <i class="las la-times" aria-hidden="true"></i>
                                <span class="visually-hidden">remover busca</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php foreach ($filters['categoryIds'] as $catId): ?>
                        <li>
                            <a class="active-filter" href="<?= htmlspecialchars($catalogoUrl(['categoryIds' => array_values(array_diff($filters['categoryIds'], [$catId]))])) ?>">
                                <?= htmlspecialchars($nomePorCategoria[$catId] ?? ('#' . $catId)) ?> <i class="las la-times" aria-hidden="true"></i>
                                <span class="visually-hidden">remover categoria</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="empty glass" role="status">
                    <i class="las la-utensils" aria-hidden="true"></i>
                    <h3><?= htmlspecialchars($errorMessage) ?></h3>
                    <p style="margin-inline:auto;">Tente outro ingrediente ou ajuste os filtros.</p>
                    <a class="btn btn--soft" href="index.php">Ver todas as receitas</a>
                </div>
            <?php else: ?>
                <ul class="recipe-grid" id="gradeReceitas" role="list" style="list-style:none; padding:0;">
                    <?php foreach ($cards as $cardIndex => $card): ?>
                        <?php require __DIR__ . '/partials/recipe-card.php'; ?>
                    <?php endforeach; ?>
                </ul>

                <?php if ((int) $pagination['totalPages'] > 1): ?>
                    <nav class="pagination" id="pagination"
                         data-next="<?= $pagination['hasMore'] ? htmlspecialchars($catalogoUrl(['page' => (int) $pagination['page'] + 1])) : '' ?>"
                         aria-label="Paginação das receitas">
                        <?php if ((int) $pagination['page'] > 1): ?>
                            <a class="btn btn--ghost" rel="prev" href="<?= htmlspecialchars($catalogoUrl(['page' => (int) $pagination['page'] - 1])) ?>">
                                <i class="las la-angle-left" aria-hidden="true"></i> Anterior
                            </a>
                        <?php endif; ?>
                        <span class="pagination__status">Página <?= (int) $pagination['page'] ?> de <?= (int) $pagination['totalPages'] ?></span>
                        <?php if ($pagination['hasMore']): ?>
                            <a class="btn btn--ghost" rel="next" href="<?= htmlspecialchars($catalogoUrl(['page' => (int) $pagination['page'] + 1])) ?>">
                                Próxima <i class="las la-angle-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/home.js" defer></script>
    <script src="./assets/js/catalog.js" defer></script>
</body>
</html>
