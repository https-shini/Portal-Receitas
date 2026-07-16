<?php

declare(strict_types=1);

/**
 * Logout tradicional acionado pelo link do menu do usuário na home.
 * Encerra a sessão e redireciona para a tela de login (o caminho relativo
 * ../../ resolve a partir de /assets/php/).
 */

$services = require __DIR__ . '/../../../config/bootstrap.php';
$redirect = $services['authController']->logout();

header('Location: ' . $redirect);
exit;
