<?php

use App\Application\Support\Slug;
use App\Application\Support\VideoEmbed;

/**
 * Página individual de receita — layout ÚNICO e reutilizável (padrão de
 * "página de produto"): só os dados mudam. Substitui o antigo modal.
 *
 * @var array $viewData ['recipe' => array, 'related' => list<array>]
 * @var bool  $isLogged
 */
$recipe = $viewData['recipe'];
$related = $viewData['related'] ?? [];
$isLogged = $isLogged ?? false;

$slug = Slug::make((string) $recipe['name']);
$ingredientes = array_values(array_filter(
    $recipe['ingredients'],
    static fn (string $i): bool => $i !== 'Não há mais ingredientes',
));
$preparo = array_map(
    static fn (string $s): string => (string) preg_replace('/^\d+\.\s*/', '', $s),
    $recipe['preparation'],
);

// JSON-LD Recipe completo — legítimo, pois o conteúdo é realmente renderizado
// nesta página (sem cloaking). Tempo em ISO-8601 quando extraível do texto.
$minutos = preg_match('/(\d+)/', (string) $recipe['time'], $m) ? (int) $m[1] : null;
$jsonLd = json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Recipe',
    'name' => $recipe['name'],
    'image' => './assets/img/' . $recipe['image'],
    'recipeCategory' => $recipe['category'],
    'recipeYield' => $recipe['servings'] > 0 ? $recipe['servings'] . ' porções' : null,
    'totalTime' => $minutos !== null ? 'PT' . $minutos . 'M' : null,
    'recipeIngredient' => $ingredientes,
    'recipeInstructions' => array_map(
        static fn (string $step): array => ['@type' => 'HowToStep', 'text' => $step],
        $preparo,
    ),
], static fn ($v): bool => $v !== null && $v !== []), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php
$pageTitle = $recipe['name'] . ' · HomeMadeGourmet';
$pageDescription = 'Receita de ' . $recipe['name'] . ' (' . $recipe['category'] . '): ingredientes, modo de preparo e vídeo passo a passo.';
// home.css fornece .recipe-card, .recipe-grid e .video-consent (compartilhados
// com o catálogo); recipe.css traz o layout específico da página.
$pageCss = ['pages/home.css', 'pages/recipe.css'];
$extraHead = "<link rel=\"canonical\" href=\"receita/{$recipe['id']}/{$slug}\">\n"
    . "    <script type=\"application/ld+json\">{$jsonLd}</script>";
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <main id="conteudo" class="recipe-page container" role="main">
        <nav class="breadcrumb" aria-label="Você está em">
            <a href="index.php">Início</a>
            <span aria-hidden="true">/</span>
            <?php if ($recipe['categoryId'] > 0): ?>
                <a href="index.php?categoriaReceita=<?= (int) $recipe['categoryId'] ?>&amp;buscar=1"><?= htmlspecialchars((string) $recipe['category']) ?></a>
                <span aria-hidden="true">/</span>
            <?php endif; ?>
            <span aria-current="page"><?= htmlspecialchars((string) $recipe['name']) ?></span>
        </nav>

        <article class="recipe">
            <header class="recipe__head">
                <div class="recipe__media glass">
                    <img src="./assets/img/<?= htmlspecialchars((string) $recipe['image']) ?>"
                         alt="Foto da receita <?= htmlspecialchars((string) $recipe['name']) ?>"
                         width="800" height="600">
                </div>

                <div class="recipe__intro">
                    <h1 class="recipe__title"><?= htmlspecialchars((string) $recipe['name']) ?></h1>

                    <ul class="recipe__facts" aria-label="Informações da receita">
                        <li class="badge"><i class="las la-tag" aria-hidden="true"></i><?= htmlspecialchars((string) $recipe['category']) ?></li>
                        <li class="badge"><i class="las la-clock" aria-hidden="true"></i><?= htmlspecialchars((string) $recipe['time']) ?></li>
                        <?php if (!empty($recipe['cookTime'])): ?>
                            <li class="badge"><i class="las la-fire" aria-hidden="true"></i>Cozimento <?= htmlspecialchars((string) $recipe['cookTime']) ?></li>
                        <?php endif; ?>
                        <li class="badge"><i class="las la-utensils" aria-hidden="true"></i><?= (int) $recipe['servings'] ?> porções</li>
                        <?php if (!empty($recipe['difficulty'])): ?>
                            <li class="badge"><i class="las la-signal" aria-hidden="true"></i><?= htmlspecialchars((string) $recipe['difficulty']) ?></li>
                        <?php endif; ?>
                        <li class="badge"><i class="las la-bolt" aria-hidden="true"></i><?= htmlspecialchars((string) $recipe['calories']) ?> cal</li>
                    </ul>

                    <?php $rating = $recipe['rating']; $notaUsuario = $userScore ?? null; ?>
                    <div class="recipe__rating" aria-label="Avaliação média">
                        <?php if ($rating['count'] > 0): ?>
                            <i class="las la-star" aria-hidden="true"></i>
                            <span class="rating-value"><?= htmlspecialchars(number_format((float) $rating['average'], 1, ',', '')) ?></span>
                            <span class="rating-count">(<?= (int) $rating['count'] ?> avaliaç<?= (int) $rating['count'] === 1 ? 'ão' : 'ões' ?>)</span>
                        <?php else: ?>
                            <span class="rating-empty">Sem avaliações ainda</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isLogged): ?>
                        <div class="rate-widget js-rate" role="radiogroup" aria-label="Sua avaliação"
                             data-id="<?= (int) $recipe['id'] ?>"
                             data-csrf="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>"
                             data-score="<?= (int) $notaUsuario ?>">
                            <span class="rate-widget__label">Sua nota:</span>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <?php $on = $notaUsuario !== null && $s <= $notaUsuario; ?>
                                <button type="button" class="rate-star<?= $on ? ' is-on' : '' ?>" data-score="<?= $s ?>"
                                        aria-label="<?= $s ?> estrela<?= $s > 1 ? 's' : '' ?>"
                                        aria-pressed="<?= $notaUsuario === $s ? 'true' : 'false' ?>">
                                    <i class="<?= $on ? 'las' : 'lar' ?> la-star" aria-hidden="true"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                    <?php else: ?>
                        <a class="rate-login" href="login.php?erro=1">Entre para avaliar</a>
                    <?php endif; ?>

                    <div class="recipe__actions">
                        <?php if ($isLogged): ?>
                            <button type="button" class="btn btn--soft js-favorito<?= !empty($isFavorite) ? ' is-active' : '' ?>"
                                    data-id="<?= (int) $recipe['id'] ?>"
                                    data-csrf="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>"
                                    aria-pressed="<?= !empty($isFavorite) ? 'true' : 'false' ?>">
                                <i class="<?= !empty($isFavorite) ? 'las la-heart' : 'lar la-heart' ?>" aria-hidden="true"></i>
                                <span class="js-favorito-label"><?= !empty($isFavorite) ? 'Favoritada' : 'Favoritar' ?></span>
                            </button>
                        <?php else: ?>
                            <a class="btn btn--soft" href="login.php?erro=1">
                                <i class="lar la-heart" aria-hidden="true"></i> Favoritar
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn--soft js-compartilhar"
                                data-title="<?= htmlspecialchars((string) $recipe['name']) ?>">
                            <i class="las la-share-alt" aria-hidden="true"></i> Compartilhar
                        </button>
                    </div>
                </div>
            </header>

            <div class="recipe__grid">
                <div class="recipe__col">
                    <section class="recipe-section" aria-labelledby="tituloVideo">
                        <h2 id="tituloVideo"><i class="las la-play-circle" aria-hidden="true"></i> Vídeo</h2>
                        <div class="recipe__video video-consent js-video-consent">
                            <div class="video-consent__box">
                                <i class="las la-play-circle" aria-hidden="true"></i>
                                <p>O vídeo é exibido pelo YouTube (youtube-nocookie.com). Ao carregar, seu IP será compartilhado com o Google — <a href="privacidade.php" target="_blank" rel="noopener">saiba mais</a>.</p>
                                <button type="button" class="btn btn--primary js-carregar-video">Carregar vídeo</button>
                            </div>
                            <template class="js-video-tpl"><?= VideoEmbed::prepare((string) $recipe['video']) ?></template>
                        </div>
                    </section>

                    <?php if (!empty($recipe['tips'])): ?>
                        <section class="recipe-section" aria-labelledby="tituloDicas">
                            <h2 id="tituloDicas"><i class="las la-lightbulb" aria-hidden="true"></i> Dicas</h2>
                            <p class="recipe__tips"><?= nl2br(htmlspecialchars((string) $recipe['tips'])) ?></p>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="recipe__col">
                    <section class="recipe-section" aria-labelledby="tituloIngredientes">
                        <h2 id="tituloIngredientes"><i class="las la-shopping-basket" aria-hidden="true"></i> Ingredientes</h2>
                        <ul class="recipe__ingredients">
                            <?php foreach ($ingredientes as $ingrediente): ?>
                                <li><?= htmlspecialchars($ingrediente) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="recipe-section" aria-labelledby="tituloPreparo">
                        <h2 id="tituloPreparo"><i class="las la-mortar-pestle" aria-hidden="true"></i> Modo de preparo</h2>
                        <ol class="recipe__steps">
                            <?php foreach ($preparo as $passo): ?>
                                <li><?= htmlspecialchars($passo) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                </div>
            </div>
        </article>

        <?php if ($related !== []): ?>
            <section class="related" aria-labelledby="tituloRelacionadas">
                <h2 id="tituloRelacionadas" class="related__title">Receitas relacionadas</h2>
                <ul class="recipe-grid" role="list" style="list-style:none; padding:0;">
                    <?php foreach ($related as $cardIndex => $card): ?>
                        <?php require __DIR__ . '/partials/recipe-card.php'; ?>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
    <script src="./assets/js/recipe.js" defer></script>
</body>
</html>
