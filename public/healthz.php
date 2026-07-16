<?php

declare(strict_types=1);

/**
 * Health check de vivacidade consumido pelos healthchecks da Render e do
 * docker compose. Deliberadamente NÃO toca o banco: um banco fora do ar não
 * deve derrubar/reiniciar a instância web (que já degrada com a página 503).
 */

header('Content-Type: text/plain; charset=utf-8');
http_response_code(200);
echo 'ok';
