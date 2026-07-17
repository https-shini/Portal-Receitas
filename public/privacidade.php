<?php

declare(strict_types=1);

/** Entrypoint da Política de Privacidade (página pública). */

$services = require __DIR__ . '/../config/bootstrap.php';
$services['sessionManager']->start();
$isLogged = !empty($services['sessionManager']->get('logado'));

require __DIR__ . '/../src/Presentation/View/privacy.php';
