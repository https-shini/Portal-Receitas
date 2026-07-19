<?php

declare(strict_types=1);

/**
 * POST /api/comments.php — publica um comentário (padrão) ou remove um
 * comentário do próprio usuário (quando acao=excluir).
 * Corpo JSON: { "idReceita": int, "texto": string, "_csrf": string }
 *          ou { "acao": "excluir", "idComentario": int, "_csrf": string }.
 * Exige sessão + CSRF; limita a frequência (200 · 201 · 400 · 401 · 403 · 429 · 503).
 */

require __DIR__ . '/_bootstrap.php';

apiRequireMethod('POST');

try {
    if (($apiInput['acao'] ?? '') === 'excluir') {
        apiRespond($services['commentController']->delete($apiInput));
    }
    apiRespond($services['commentController']->post($apiInput));
} catch (PDOException) {
    apiUnavailable();
}
