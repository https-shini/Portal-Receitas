<?php
/**
 * Página 404 — receita ou rota inexistente. Usa os partials globais para
 * permanecer navegável (header/rodapé), ao contrário de unavailable.php, que
 * é autocontida por ser exibida quando o próprio banco está fora.
 *
 * @var bool $isLogged Estado de autenticação (definido pelo entrypoint).
 */
$isLogged = $isLogged ?? false;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php
$pageTitle = 'Página não encontrada · HomeMadeGourmet';
$pageDescription = 'A página ou receita que você procura não foi encontrada.';
$pageCss = [];
$robotsNoindex = true;
require __DIR__ . '/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

    <main id="conteudo" class="container" role="main" style="padding-block: var(--space-9);">
        <div class="empty glass" role="status">
            <i class="las la-utensils" aria-hidden="true"></i>
            <h1 style="font-size: var(--text-2xl);">Não encontramos essa receita</h1>
            <p style="margin-inline:auto;">O link pode estar incorreto ou a receita saiu do catálogo. Que tal explorar as outras?</p>
            <a class="btn btn--primary" href="index.php">
                <i class="las la-utensils" aria-hidden="true"></i> Ver todas as receitas
            </a>
        </div>
    </main>

<?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
</body>
</html>
