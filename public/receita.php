<?php

declare(strict_types=1);

/**
 * Entrypoint da página individual de receita — rota /receita/{id}/{slug}
 * (reescrita por public/.htaccess) com fallback receita.php?id={id}.
 *
 * Valida o id, busca a receita e as relacionadas via RecipeController e
 * renderiza o layout único. Id inválido ou receita inexistente → 404.
 * Banco indisponível → 503 amigável.
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

// Todo o acesso ao banco desta página fica sob o mesmo try: favoritos,
// avaliação e comentários também consultam o banco, e fora do try uma queda
// do MariaDB (ex.: OOM no plano free) viraria erro fatal 500 em vez do 503
// amigável já implementado para a busca da receita.
try {
    $viewData = $services['recipeController']->show($id);

    if ($viewData === null) {
        http_response_code(404);
        require __DIR__ . '/../src/Presentation/View/not-found.php';
        exit;
    }

    // Estado de favorito e token CSRF só interessam ao usuário autenticado
    // (o botão de favoritar aparece apenas para quem está logado).
    $isFavorite = false;
    $userScore = null;
    $csrfToken = '';
    if ($isLogged) {
        $isFavorite = $services['favoriteController']->isFavorite($id);
        $userScore = $services['ratingController']->userScore($id);
        $csrfToken = $services['sessionManager']->csrfToken();
    }

    // Comentários (leitura pública; publicar exige login).
    $comments = $services['commentController']->forRecipe($id);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

require __DIR__ . '/../src/Presentation/View/recipe.php';
