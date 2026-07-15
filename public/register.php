<?php

declare(strict_types=1);

$services = require __DIR__ . '/../config/bootstrap.php';

$sessionManager = $services['sessionManager'];
$sessionManager->start();

if (!empty($sessionManager->get('logado'))) {
    header('Location: index.php');
    exit;
}

$initialPanel = 'register';

require __DIR__ . '/../src/Presentation/View/auth.php';
