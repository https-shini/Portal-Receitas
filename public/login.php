<?php

declare(strict_types=1);

$services = require __DIR__ . '/../config/bootstrap.php';

try {
    $viewData = $services['authController']->login($_POST);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

if (isset($viewData['redirect'])) {
    header('Location: ' . $viewData['redirect']);
    exit;
}

require __DIR__ . '/../src/Presentation/View/login.php';
