<?php

declare(strict_types=1);

/** POST /api/login.php — autenticação e abertura de sessão (200 · 401 · 405 · 503). */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['authController']->login($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
