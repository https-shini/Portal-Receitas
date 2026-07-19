<?php

declare(strict_types=1);

/**
 * POST /api/ratings.php — registra/atualiza (nota 1–5) ou remove (nota 0) a
 * avaliação da receita pelo usuário autenticado. Corpo JSON:
 * { "idReceita": int, "nota": int, "_csrf": string }.
 * Exige sessão + CSRF; limita a frequência (200 · 400 · 401 · 403 · 429 · 503).
 */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    apiRespond($services['ratingController']->rate($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
