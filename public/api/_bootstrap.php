<?php

declare(strict_types=1);

/**
 * Bootstrap comum dos endpoints JSON da API de autenticação.
 * Define $services, $apiInput e a função apiRespond().
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$services = require __DIR__ . '/../../config/bootstrap.php';

$apiInput = [];
$rawBody = file_get_contents('php://input');
if ($rawBody !== false && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $apiInput = $decoded;
    }
}
if ($apiInput === []) {
    $apiInput = $_POST;
}

function apiRespond(array $result): never
{
    http_response_code($result['status']);
    echo json_encode($result['body'], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiRequireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        apiRespond(['status' => 405, 'body' => ['detail' => 'Método não permitido.']]);
    }
}

function apiUnavailable(): never
{
    apiRespond(['status' => 503, 'body' => ['detail' => 'Serviço temporariamente indisponível. Tente novamente em instantes.']]);
}
