<?php

declare(strict_types=1);

/**
 * Entrypoint da página individual de receita — rota /receita/{id}/{slug}
 * (reescrita por public/.htaccess) com fallback receita.php?id={id}.
 *
 * E1 (infraestrutura de rota): valida o id e trata o 404. A renderização
 * completa da receita (conteúdo migrado do modal, vídeo, relacionadas) chega
 * na etapa E3 — por ora exibe um marcador de construção para o id válido.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

$services['sessionManager']->start();
$isLogged = !empty($services['sessionManager']->get('logado'));

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    http_response_code(404);
    require __DIR__ . '/../src/Presentation/View/not-found.php';
    exit;
}

// Marcador da etapa E1 — substituído pela view real da receita em E3.
$recipeId = $id;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php
$pageTitle = 'Receita · HomeMadeGourmet';
$pageDescription = 'Página de receita do HomeMadeGourmet.';
$pageCss = [];
$robotsNoindex = true;
require __DIR__ . '/../src/Presentation/View/partials/head.php';
?>
</head>
<body>
<?php require __DIR__ . '/../src/Presentation/View/partials/header.php'; ?>

    <main id="conteudo" class="container" role="main" style="padding-block: var(--space-9);">
        <div class="empty glass" role="status">
            <i class="las la-hard-hat" aria-hidden="true"></i>
            <h1 style="font-size: var(--text-2xl);">Página de receita em construção</h1>
            <p style="margin-inline:auto;">A rota já resolve a receita <strong>#<?= (int) $recipeId ?></strong>. O conteúdo completo chega na próxima etapa da refatoração.</p>
            <a class="btn btn--soft" href="index.php">← Voltar às receitas</a>
        </div>
    </main>

<?php require __DIR__ . '/../src/Presentation/View/partials/footer.php'; ?>

    <script src="./assets/js/theme.js" defer></script>
</body>
</html>
