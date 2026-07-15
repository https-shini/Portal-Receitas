<?php

declare(strict_types=1);

// Health check leve para plataformas de deploy (Render usa healthCheckPath).
// Não toca o banco: responde 200 se o PHP/Apache estão de pé.
header('Content-Type: text/plain; charset=utf-8');
http_response_code(200);
echo 'ok';
