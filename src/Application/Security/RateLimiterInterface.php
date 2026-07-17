<?php

declare(strict_types=1);

namespace App\Application\Security;

/**
 * Contrato de limitação de taxa por chave (proteção contra força bruta).
 *
 * A chave identifica o alvo protegido (ex.: "login|<ip>|<email>"); a janela
 * é deslizante. Implementação concreta injetada no composition root.
 */
interface RateLimiterInterface
{
    /** Indica se a chave excedeu $maxAttempts dentro da janela de $windowSeconds. */
    public function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool;

    /** Registra uma tentativa para a chave (chamado a cada falha). */
    public function hit(string $key, int $windowSeconds): void;

    /** Zera o contador da chave (chamado após sucesso, para não punir o titular legítimo). */
    public function clear(string $key): void;
}
