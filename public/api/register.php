<?php

declare(strict_types=1);

/** POST /api/register.php — cadastro de usuário (201 · 400 · 405 · 503). */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['authController']->register($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
