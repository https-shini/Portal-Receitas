<?php

declare(strict_types=1);

/** Prontidão do catálogo; healthz.php continua sendo a checagem de vivacidade. */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

// Metadado público da Render permite conferir qual commit está em execução.
$commit = getenv('RENDER_GIT_COMMIT');
if (is_string($commit) && preg_match('/\A[0-9a-f]{40}\z/i', $commit) === 1) {
    header('X-App-Commit: ' . $commit);
}

try {
    $services = require __DIR__ . '/../config/bootstrap.php';
    $services['recipeController']->list([]);
    echo 'ok';
} catch (Throwable $error) {
    error_log('[readyz] Catálogo indisponível: ' . $error->getMessage());
    http_response_code(503);
    echo 'unavailable';
}
