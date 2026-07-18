<?php
/**
 * Página "Minhas favoritas" (rota protegida). Reaproveita a grade e o card
 * do catálogo. Estado vazio com CTA para explorar receitas.
 *
 * @var array $viewData ['cards' => list<array>]
 * @var bool  $isLogged
 */
$cards = $viewData['cards'] ?? [];
$isLogged = $isLogged ?? true;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php
$pageTitle = 'Minhas favoritas · HomeMadeGourmet';
$pageDescription = 'Suas receitas favoritas salvas no HomeMadeGourmet.';
$pageCss = ['pages/home.css'];
$robotsNoindex = true;
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <main id="conteudo" class="results container" role="main" style="padding-block: var(--space-6) var(--space-9);">
        <div class="results__head">
            <h1 id="tituloResultados" style="font-size: var(--text-2xl);"><i class="las la-heart" aria-hidden="true"></i> Minhas favoritas</h1>
            <span class="results__count" role="status"><?= count($cards) ?> receita<?= count($cards) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($cards === []): ?>
            <div class="empty glass" role="status">
                <i class="las la-heart" aria-hidden="true"></i>
                <h2 style="font-size: var(--text-xl);">Você ainda não tem favoritas</h2>
                <p style="margin-inline:auto;">Explore o catálogo e toque no coração das receitas que quiser salvar aqui.</p>
                <a class="btn btn--primary" href="index.php"><i class="las la-utensils" aria-hidden="true"></i> Ver receitas</a>
            </div>
        <?php else: ?>
            <ul class="recipe-grid" role="list" style="list-style:none; padding:0;">
                <?php foreach ($cards as $cardIndex => $card): ?>
                    <?php require __DIR__ . '/partials/recipe-card.php'; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
</body>
</html>
