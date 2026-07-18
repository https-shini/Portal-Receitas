<?php

declare(strict_types=1);

/**
 * Entrypoint das receitas favoritas — rota protegida. Visitante não
 * autenticado é redirecionado ao login. Banco indisponível → 503 amigável.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

$sessionManager = $services['sessionManager'];
$sessionManager->start();

if (empty($sessionManager->get('logado'))) {
    header('Location: login.php?erro=1');
    exit;
}

$isLogged = true;

try {
    $viewData = $services['favoriteController']->favorites();
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

require __DIR__ . '/../src/Presentation/View/favorites.php';
