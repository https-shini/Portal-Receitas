<?php

declare(strict_types=1);

/**
 * Entrypoint da home (página pública): catálogo, busca e filtro de receitas.
 * Banco indisponível → responde 503 com a página autocontida de
 * indisponibilidade em vez de erro bruto.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

try {
    $viewData = $services['recipeController']->list($_GET);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

// O catálogo é público; a sessão só reflete o estado do header (menu do
// usuário para autenticados, botão "Entrar" para visitantes).
$services['sessionManager']->start();
$isLogged = !empty($services['sessionManager']->get('logado'));

require __DIR__ . '/../src/Presentation/View/index.php';
