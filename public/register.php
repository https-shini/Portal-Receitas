<?php

declare(strict_types=1);

/**
 * Entrypoint da tela de acesso no estado "cadastro" (mesma view do login,
 * painel inicial diferente). Usuário autenticado é redirecionado à home.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

$sessionManager = $services['sessionManager'];
$sessionManager->start();

if (!empty($sessionManager->get('logado'))) {
    header('Location: index.php');
    exit;
}

$initialPanel = 'register';

require __DIR__ . '/../src/Presentation/View/auth.php';
