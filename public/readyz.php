<?php

declare(strict_types=1);

/** Prontidão do catálogo; healthz.php continua sendo a checagem de vivacidade. */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

try {
    $services = require __DIR__ . '/../config/bootstrap.php';
    $services['recipeController']->list([]);
    echo 'ok';
} catch (Throwable $error) {
    error_log('[readyz] Catálogo indisponível: ' . $error->getMessage());
    http_response_code(503);
    echo 'unavailable';
}
