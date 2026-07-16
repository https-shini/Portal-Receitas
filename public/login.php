<?php

declare(strict_types=1);

/**
 * Entrypoint da tela de acesso no estado "login".
 *
 * Usuário já autenticado é redirecionado à home (não faz sentido reexibir o
 * formulário). O parâmetro ?erro=true — usado pelo guard do perfil — liga o
 * aviso "Você precisa logar para entrar no site" na view.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

$sessionManager = $services['sessionManager'];
$sessionManager->start();

if (!empty($sessionManager->get('logado'))) {
    header('Location: index.php');
    exit;
}

$initialPanel = 'login';
$erroLogin = !empty($_GET['erro']);

require __DIR__ . '/../src/Presentation/View/auth.php';
