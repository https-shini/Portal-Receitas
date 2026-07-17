<?php
/** @var array $viewData */
$cards = $viewData['cards'] ?? [];
$details = $viewData['details'] ?? [];
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
 * Preparação do embed vindo do banco: força o domínio de privacidade
 * reforçada (youtube-nocookie.com — cobre bancos semeados por versões
 * antigas do seed) e adiciona lazy loading, sem alterar o restante do HTML.
 */
$prepararEmbed = static function (string $html): string {
    $html = str_ireplace(['www.youtube.com/embed', 'youtube.com/embed'], ['www.youtube-nocookie.com/embed', 'youtube-nocookie.com/embed'], $html);

    return str_ireplace('<iframe ', '<iframe loading="lazy" ', $html);
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeMadeGourmet — Receitas caseiras para o seu gosto</title>
    <meta name="description" content="Portal de receitas caseiras: busque por ingrediente, filtre por categoria e siga o passo a passo com vídeo. Frutos do mar, massas, veganas, salgados, doces e carnes.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="HomeMadeGourmet — Receitas caseiras para o seu gosto">
    <meta property="og:description" content="Busque receitas por ingrediente, filtre por categoria e cozinhe com vídeo passo a passo.">
    <meta property="og:image" content="./assets/img/Logo.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="HomeMadeGourmet">
    <meta name="twitter:description" content="Receitas caseiras com busca por ingrediente e vídeo passo a passo.">
    <meta name="theme-color" content="#C2410C">
    <link rel="icon" href="./assets/img/logoIcon.png">
    <script>
        (function () {
            var t;
            try { t = localStorage.getItem("hmg_theme"); } catch (e) {}
            if (!t) t = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            document.documentElement.setAttribute("data-theme", t);
        })();
    </script>
    <link rel="stylesheet" href="./assets/css/fonts.css">
    <link rel="stylesheet" href="./assets/vendor/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/base.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/home.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "HomeMadeGourmet",
        "description": "Portal de receitas caseiras com busca por ingrediente e filtro por categoria.",
        "inLanguage": "pt-BR"
    }
    </script>
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="site-header" role="banner">
        <div class="container site-header__row">
            <a class="brand" href="index.php" aria-label="HomeMadeGourmet — início">
                <img src="./assets/img/logoIcon.png" alt="" width="36" height="36">
                <span class="brand__name">HomeMadeGourmet</span>
            </a>

            <details class="user-menu" id="userMenu">
                <summary aria-label="Abrir menu do usuário">
                    <span class="btn btn--ghost btn--icon" aria-hidden="true"><i class="las la-user"></i></span>
                </summary>
                <nav class="user-menu__panel" aria-label="Menu do usuário">
                    <a class="user-menu__item" href="profile.php">
                        <i class="las la-user-cog" aria-hidden="true"></i> Alterar Informações
                    </a>
                    <a class="user-menu__item" href="./assets/php/logout.php">
                        <i class="las la-sign-out-alt" aria-hidden="true"></i> Sair da conta
                    </a>
                </nav>
            </details>

            <button type="button" class="btn btn--ghost btn--icon js-theme-toggle" aria-label="Alternar tema claro/escuro" aria-pressed="false">
                <i class="las la-moon" aria-hidden="true"></i>
            </button>
        </div>
    </header>

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
                    <?php foreach ($cards as $i => $recipe): ?>
                        <li class="card recipe-card reveal" style="animation-delay: <?= min($i * 40, 400) ?>ms">
                            <button type="button" class="recipe-card__btn js-abrir-receita"
                                    data-receita="<?= (int) $recipe['id'] ?>"
                                    aria-haspopup="dialog"
                                    aria-label="Abrir receita: <?= htmlspecialchars($recipe['name']) ?>">
                                <span class="recipe-card__media">
                                    <img src="./assets/img/<?= htmlspecialchars($recipe['image']) ?>"
                                         alt="" loading="lazy" width="400" height="300">
                                </span>
                                <span class="recipe-card__body">
                                    <span class="recipe-card__title"><?= htmlspecialchars($recipe['name']) ?></span>
                                    <span class="recipe-card__meta">
                                        <span><i class="las la-clock" aria-hidden="true"></i><?= htmlspecialchars($recipe['time']) ?></span>
                                        <span><i class="las la-tag" aria-hidden="true"></i><?= htmlspecialchars($recipe['category']) ?></span>
                                    </span>
                                </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>

    <!-- ── Modal de receita (conteúdo vem dos <template>, iframes só carregam ao abrir) ── -->
    <div class="modal-backdrop" id="modalReceita" role="dialog" aria-modal="true" aria-labelledby="modalTitulo" hidden>
        <article class="modal" id="modalConteudo"></article>
    </div>

    <?php foreach ($details as $recipe): ?>
        <template id="tplReceita<?= (int) $recipe['id'] ?>">
            <header class="recipe-modal__head">
                <h2 id="modalTitulo"><?= htmlspecialchars($recipe['name']) ?></h2>
                <button type="button" class="btn btn--ghost btn--icon js-fechar-receita" aria-label="Fechar receita">
                    <i class="las la-times" aria-hidden="true"></i>
                </button>
            </header>
            <div class="recipe-modal__grid">
                <div>
                    <div class="recipe-modal__video video-consent js-video-consent">
                        <div class="video-consent__box">
                            <i class="las la-play-circle" aria-hidden="true"></i>
                            <p>O vídeo é exibido pelo YouTube (youtube-nocookie.com). Ao carregar, seu IP será compartilhado com o Google — <a href="privacidade.php" target="_blank" rel="noopener">saiba mais</a>.</p>
                            <button type="button" class="btn btn--primary js-carregar-video">Carregar vídeo</button>
                        </div>
                        <template class="js-video-tpl"><?= $prepararEmbed((string) $recipe['video']) ?></template>
                    </div>
                    <div class="recipe-modal__facts">
                        <span class="badge"><i class="las la-tag" aria-hidden="true"></i><?= htmlspecialchars($recipe['category']) ?></span>
                        <span class="badge"><i class="las la-clock" aria-hidden="true"></i><?= htmlspecialchars($recipe['time']) ?></span>
                        <span class="badge"><i class="las la-utensils" aria-hidden="true"></i><?= (int) $recipe['servings'] ?> Porções</span>
                        <span class="badge"><i class="las la-fire" aria-hidden="true"></i><?= htmlspecialchars((string) $recipe['calories']) ?> cal</span>
                    </div>
                </div>
                <div>
                    <section class="recipe-section" aria-label="Ingredientes">
                        <h3><i class="las la-shopping-basket" aria-hidden="true"></i>Ingredientes</h3>
                        <ul>
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <?php if ($ingredient !== 'Não há mais ingredientes'): ?>
                                    <li><?= htmlspecialchars($ingredient) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <section class="recipe-section" aria-label="Modo de preparo" style="margin-top: var(--space-4);">
                        <h3><i class="las la-mortar-pestle" aria-hidden="true"></i>Modo de Preparo</h3>
                        <ol>
                            <?php foreach ($recipe['preparation'] as $step): ?>
                                <li><?= htmlspecialchars(preg_replace('/^\d+\.\s*/', '', $step)) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                </div>
            </div>
        </template>
    <?php endforeach; ?>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p style="margin-inline:auto;">HomeMadeGourmet — TCC ETEC de Vila Formosa · Feito com receitas de família.<br>
            <a href="privacidade.php">Política de Privacidade</a> · <a href="termos.php">Termos de Uso</a></p>
        </div>
    </footer>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/home.js" defer></script>
</body>
</html>
