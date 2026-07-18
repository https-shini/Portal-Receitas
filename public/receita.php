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

try {
    $viewData = $services['recipeController']->show($id);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

if ($viewData === null) {
    http_response_code(404);
    require __DIR__ . '/../src/Presentation/View/not-found.php';
    exit;
}

require __DIR__ . '/../src/Presentation/View/recipe.php';
