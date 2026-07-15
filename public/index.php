<?php

declare(strict_types=1);

$services = require __DIR__ . '/../config/bootstrap.php';

try {
    $viewData = $services['recipeController']->list($_GET);
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/../src/Presentation/View/unavailable.php';
    exit;
}

require __DIR__ . '/../src/Presentation/View/index.php';
