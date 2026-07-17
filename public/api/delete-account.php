<?php

declare(strict_types=1);

/**
 * POST /api/delete-account.php — eliminação da conta pelo titular por
 * anonimização (LGPD art. 18, VI). Exige sessão ativa e reautenticação com
 * a senha atual (200 · 400 · 401 · 405 · 429 · 503).
 */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['authController']->deleteAccount($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
