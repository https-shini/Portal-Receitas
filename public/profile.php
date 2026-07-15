<?php

declare(strict_types=1);

$services = require __DIR__ . '/../config/bootstrap.php';
$viewData = $services['profileController']->handle($_POST);

if (isset($viewData['redirect'])) {
    header('Location: ' . $viewData['redirect']);
    exit;
}

require __DIR__ . '/../src/Presentation/View/profile.php';
