<?php

declare(strict_types=1);

/**
 * Entrypoint de logout do menu do usuário. Encerra a sessão e redireciona
 * para o catálogo público (PRG: sem corpo, só um Location). Idempotente —
 * acessar já deslogado apenas volta para a home.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

$destino = $services['authController']->logout();

header('Location: ' . $destino);
exit;
