<?php

declare(strict_types=1);

/**
 * POST /api/favorites.php — alterna o favorito de uma receita para o usuário
 * autenticado. Corpo JSON: { "idReceita": int, "_csrf": string }.
 * Exige sessão + token CSRF; limita a frequência (200 · 400 · 401 · 403 · 429 · 503).
 */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['favoriteController']->toggle($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
