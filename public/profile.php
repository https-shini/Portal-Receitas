<?php

declare(strict_types=1);

/**
 * Entrypoint do perfil (rota protegida — o guard vive no ProfileController).
 * GET exibe os dados; POST com 'salvar' aplica a atualização e encerra a
 * sessão em caso de sucesso. Banco indisponível → 503 amigável.
 */

$services = require __DIR__ . '/../config/bootstrap.php';

try {
    $viewData = $services['profileController']->handle($_POST);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

if (isset($viewData['redirect'])) {
    header('Location: ' . $viewData['redirect']);
    exit;
}

require __DIR__ . '/../src/Presentation/View/profile.php';
