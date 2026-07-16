<?php

declare(strict_types=1);

/**
 * Bootstrap comum dos endpoints JSON (front controller compartilhado).
 *
 * Responsabilidades: fixar os headers de resposta JSON, montar o contêiner
 * de serviços, normalizar a entrada e expor os helpers de resposta usados
 * por todos os endpoints — mantendo cada register/login/logout/me.php com
 * zero lógica própria.
 *
 * Define: $services (contêiner), $apiInput (corpo já decodificado),
 * apiRespond(), apiRequireMethod() e apiUnavailable().
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$services = require __DIR__ . '/../../config/bootstrap.php';

/*
 * Entrada: preferencialmente JSON no corpo (Content-Type: application/json);
 * fallback para $_POST cobre clientes form-urlencoded.
 */
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

/**
 * Serializa o resultado de um controller (['status' =>, 'body' =>]) como
 * resposta JSON final e encerra a requisição.
 */
function apiRespond(array $result): never
{
    http_response_code($result['status']);
    echo json_encode($result['body'], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Rejeita com 405 qualquer método HTTP diferente do esperado pelo endpoint. */
function apiRequireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        apiRespond(['status' => 405, 'body' => ['detail' => 'Método não permitido.']]);
    }
}

/** Resposta 503 padrão para banco indisponível (PDOException nos endpoints). */
function apiUnavailable(): never
{
    apiRespond(['status' => 503, 'body' => ['detail' => 'Serviço temporariamente indisponível. Tente novamente em instantes.']]);
}
