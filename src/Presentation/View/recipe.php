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

// Open Graph/Twitter exigem URLs absolutas (o crawler não resolve <base href>).
// Deriva o origin do request; usa o host/protocolo efetivos por trás de proxy.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$origin = $scheme . '://' . preg_replace('/[^\w.:-]/', '', $host);
$ogUrl = $origin . '/receita/' . (int) $recipe['id'] . '/' . $slug;
$ogImage = $origin . '/assets/img/' . rawurlencode((string) $recipe['image']);

$ogTitle = htmlspecialchars($pageTitle, ENT_QUOTES);
$ogDesc = htmlspecialchars($pageDescription, ENT_QUOTES);
$ogUrlE = htmlspecialchars($ogUrl, ENT_QUOTES);
$ogImageE = htmlspecialchars($ogImage, ENT_QUOTES);

$og = <<<HTML
<meta property="og:type" content="article">
    <meta property="og:site_name" content="HomeMadeGourmet">
    <meta property="og:title" content="{$ogTitle}">
    <meta property="og:description" content="{$ogDesc}">
    <meta property="og:url" content="{$ogUrlE}">
    <meta property="og:image" content="{$ogImageE}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$ogTitle}">
    <meta name="twitter:description" content="{$ogDesc}">
    <meta name="twitter:image" content="{$ogImageE}">
HTML;

$extraHead = "<link rel=\"canonical\" href=\"receita/{$recipe['id']}/{$slug}\">\n"
    . '    ' . $og . "\n"
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
                <?php $gallery = $recipe['gallery'] ?? [$recipe['image']]; ?>
                <div class="recipe__gallery">
                    <div class="recipe__media glass">
                        <img id="galeriaPrincipal" src="./assets/img/<?= htmlspecialchars((string) $gallery[0]) ?>"
                             alt="Foto da receita <?= htmlspecialchars((string) $recipe['name']) ?>"
                             width="800" height="600">
                    </div>
                    <?php if (count($gallery) > 1): ?>
                        <ul class="recipe__thumbs js-galeria" role="list" aria-label="Galeria de fotos">
                            <?php foreach ($gallery as $i => $arquivo): ?>
                                <li>
                                    <button type="button" class="recipe__thumb<?= $i === 0 ? ' is-active' : '' ?>"
                                            data-src="./assets/img/<?= htmlspecialchars((string) $arquivo) ?>"
                                            aria-label="Ver foto <?= $i + 1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                                        <img src="./assets/img/<?= htmlspecialchars((string) $arquivo) ?>" alt="" loading="lazy" width="120" height="90">
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
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

                    <?php
                    $n = $recipe['nutrition'];
                    $porcoesBase = max(1, (int) $recipe['servings']);
                    $fmt = static fn (float $v): string => number_format($v, 1, ',', '');
                    ?>
                    <section class="recipe-section" aria-labelledby="tituloNutricao">
                        <h2 id="tituloNutricao"><i class="las la-heartbeat" aria-hidden="true"></i> Informações nutricionais</h2>
                        <p class="recipe__nutri-note">Valores estimados por porção.</p>
                        <ul class="nutri-grid" role="list">
                            <li class="nutri-item">
                                <span class="nutri-item__value"><?= htmlspecialchars((string) $n['calories']) ?></span>
                                <span class="nutri-item__label">kcal</span>
                            </li>
                            <?php
                            $macros = [
                                ['Proteínas', $n['protein']],
                                ['Carboidratos', $n['carbs']],
                                ['Gorduras', $n['fat']],
                            ];
                            foreach ($macros as [$rotulo, $valor]):
                                if ($valor === null) {
                                    continue;
                                } ?>
                                <li class="nutri-item">
                                    <span class="nutri-item__value"><?= htmlspecialchars(number_format((float) $valor, 1, ',', '')) ?> g</span>
                                    <span class="nutri-item__label"><?= $rotulo ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="portion-calc js-portion"
                             data-servings="<?= $porcoesBase ?>"
                             data-kcal="<?= htmlspecialchars((string) $n['calories']) ?>"
                             data-p="<?= htmlspecialchars((string) ($n['protein'] ?? '')) ?>"
                             data-c="<?= htmlspecialchars((string) ($n['carbs'] ?? '')) ?>"
                             data-g="<?= htmlspecialchars((string) ($n['fat'] ?? '')) ?>">
                            <span class="portion-calc__label">Calcular o total para</span>
                            <div class="stepper" role="group" aria-label="Número de porções">
                                <button type="button" class="stepper__btn js-portion-dec" aria-label="Menos uma porção">&minus;</button>
                                <span class="stepper__value"><span class="js-portion-n"><?= $porcoesBase ?></span> porções</span>
                                <button type="button" class="stepper__btn js-portion-inc" aria-label="Mais uma porção">+</button>
                            </div>
                            <p class="portion-calc__total" aria-live="polite">
                                <b><span class="js-portion-kcal"><?= $fmt($n['calories'] * $porcoesBase) ?></span> kcal</b>
                                <?php if ($n['protein'] !== null): ?>
                                    · <span class="js-portion-p"><?= $fmt(($n['protein'] ?? 0) * $porcoesBase) ?></span> g prot.
                                    · <span class="js-portion-c"><?= $fmt(($n['carbs'] ?? 0) * $porcoesBase) ?></span> g carb.
                                    · <span class="js-portion-g"><?= $fmt(($n['fat'] ?? 0) * $porcoesBase) ?></span> g gord.
                                <?php endif; ?>
                            </p>
                        </div>
                    </section>
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

        <?php $comments = $comments ?? []; ?>
        <section class="comments" aria-labelledby="tituloComentarios">
            <h2 id="tituloComentarios" class="comments__title">
                Comentários <span class="comments__count" id="comentariosContagem">(<?= count($comments) ?>)</span>
            </h2>

            <?php if ($isLogged): ?>
                <form class="comment-form js-comment-form" data-id="<?= (int) $recipe['id'] ?>"
                      data-csrf="<?= htmlspecialchars((string) ($csrfToken ?? '')) ?>">
                    <label class="visually-hidden" for="comentarioTexto">Seu comentário</label>
                    <textarea class="field__input" id="comentarioTexto" name="texto" rows="3"
                              maxlength="500" placeholder="Compartilhe sua experiência com esta receita…" required></textarea>
                    <div class="comment-form__foot">
                        <div class="alert" id="comentarioAlerta" role="alert" aria-live="polite"></div>
                        <button type="submit" class="btn btn--primary"><i class="las la-paper-plane" aria-hidden="true"></i> Comentar</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="comments__login"><a href="login.php?erro=1">Entre</a> para deixar um comentário.</p>
            <?php endif; ?>

            <ul class="comment-list" id="comentarios" role="list">
                <?php foreach ($comments as $comentario): ?>
                    <li class="comment" data-id="<?= (int) $comentario['id'] ?>">
                        <div class="comment__head">
                            <span class="comment__author"><i class="las la-user-circle" aria-hidden="true"></i> <?= htmlspecialchars($comentario['autor']) ?></span>
                            <span class="comment__date"><?= htmlspecialchars($comentario['data']) ?></span>
                        </div>
                        <p class="comment__text"><?= nl2br(htmlspecialchars($comentario['texto'])) ?></p>
                        <?php if (!empty($comentario['mine'])): ?>
                            <button type="button" class="comment__delete js-comment-delete" aria-label="Excluir comentário">
                                <i class="las la-trash" aria-hidden="true"></i> Excluir
                            </button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="comments__empty" id="comentariosVazio"<?= $comments !== [] ? ' hidden' : '' ?>>Ainda não há comentários. Seja o primeiro!</p>
        </section>

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
