<?php
/** @var array $viewData */
$cards = $viewData['cards'] ?? [];
$errorMessage = $viewData['errorMessage'] ?? null;

$pesquisaAtual = isset($_GET['pesquisa']) ? trim((string) $_GET['pesquisa']) : '';
$categoriaAtual = isset($_GET['categoriaReceita']) && $_GET['categoriaReceita'] !== '' ? (int) $_GET['categoriaReceita'] : null;
$filtrando = isset($_GET['buscar']);

$categorias = [
    1 => ['sushiIcone.png', 'Frutos do Mar'],
    2 => ['massaIcone.png', 'Massas'],
    3 => ['veganoIcone.png', 'Veganas'],
    4 => ['croassaIcone.png', 'Salgados'],
    5 => ['boloIcone.png', 'Doces'],
    6 => ['carneIcone.png', 'Carnes'],
];

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
$pageCss = ['pages/home.css'];
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
        <!-- ── Busca e filtros ── -->
        <section class="search-hero container" aria-labelledby="tituloBusca">
            <h1 id="tituloBusca">O que vamos cozinhar hoje?</h1>
            <p>Digite o ingrediente da sua receita ou selecione a categoria pra filtrar.</p>

            <form action="index.php" method="GET" id="formSearch" role="search" aria-label="Buscar receitas">
                <div class="search-bar glass">
                    <div class="field__control">
                        <i class="las la-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="campoPesquisa">Ingrediente da receita</label>
                        <input class="field__input" id="campoPesquisa" name="pesquisa" type="search"
                               placeholder="Ex.: bacon, leite, chocolate…"
                               value="<?= htmlspecialchars($pesquisaAtual) ?>">
                    </div>
                    <button class="btn btn--primary" type="submit" name="buscar" id="btnBuscar">
                        Buscar<span class="visually-hidden"> receitas</span>
                    </button>
                </div>

                <fieldset class="cat-filter">
                    <legend class="visually-hidden">Filtrar por categoria</legend>
                    <?php foreach ($categorias as $id => $categoria): ?>
                        <label class="chip">
                            <input type="radio" name="categoriaReceita" value="<?= $id ?>"
                                   <?= $categoriaAtual === $id ? 'checked' : '' ?>>
                            <span class="chip__body">
                                <img src="./assets/img/<?= htmlspecialchars($categoria[0]) ?>" alt="" width="28" height="28">
                                <?= htmlspecialchars($categoria[1]) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            </form>
        </section>

        <!-- ── Resultados ── -->
        <section class="results container" aria-labelledby="tituloResultados">
            <div class="results__head">
                <h2 id="tituloResultados"><?= $filtrando ? 'Sua pesquisa' : 'Receitas' ?></h2>
                <span class="results__count" role="status"><?= count($cards) ?> receita<?= count($cards) === 1 ? '' : 's' ?></span>
                <?php if ($filtrando): ?>
                    <a class="results__clear" href="index.php">Limpar filtros</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="empty glass" role="status">
                    <i class="las la-utensils" aria-hidden="true"></i>
                    <h3><?= htmlspecialchars($errorMessage) ?></h3>
                    <p style="margin-inline:auto;">Experimente outro ingrediente ou escolha uma categoria acima.</p>
                    <a class="btn btn--soft" href="index.php">Ver todas as receitas</a>
                </div>
            <?php elseif ($cards === []): ?>
                <div class="empty glass" role="status">
                    <i class="las la-utensils" aria-hidden="true"></i>
                    <h3>Não foi possível encontrar receitas :(</h3>
                    <a class="btn btn--soft" href="index.php">Ver todas as receitas</a>
                </div>
            <?php else: ?>
                <ul class="recipe-grid" id="gradeReceitas" role="list" style="list-style:none; padding:0;">
                    <?php foreach ($cards as $cardIndex => $card): ?>
                        <?php require __DIR__ . '/partials/recipe-card.php'; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/home.js" defer></script>
</body>
</html>
