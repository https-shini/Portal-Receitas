<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Security\RateLimiterInterface;

/**
 * Rate limiter com janela deslizante persistido em arquivos no diretório
 * temporário do sistema.
 *
 * Escolha deliberada (KISS): não exige nenhuma dependência nem mudança de
 * schema e atende ao deploy atual de instância única. Limitação conhecida:
 * o estado é por instância — em escala horizontal, substituir por uma
 * implementação compartilhada (Redis) mantendo esta interface.
 */
class FileRateLimiter implements RateLimiterInterface
{
    private readonly string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? sys_get_temp_dir() . '/hmg-ratelimit';

        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0700, true);
        }
    }

    public function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        return count($this->recentAttempts($key, $windowSeconds)) >= $maxAttempts;
    }

    public function hit(string $key, int $windowSeconds): void
    {
        $attempts = $this->recentAttempts($key, $windowSeconds);
        $attempts[] = time();

        @file_put_contents($this->pathFor($key), json_encode($attempts), LOCK_EX);
    }

    public function clear(string $key): void
    {
        @unlink($this->pathFor($key));
    }

    /**
     * Carrega os timestamps da chave e descarta os que já saíram da janela —
     * o próprio ato de ler poda o histórico.
     *
     * @return list<int>
     */
    private function recentAttempts(string $key, int $windowSeconds): array
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return [];
        }

        $raw = @file_get_contents($path);
        $attempts = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($attempts)) {
            return [];
        }

        $cutoff = time() - $windowSeconds;

        return array_values(array_filter(
            $attempts,
            static fn ($timestamp) => is_int($timestamp) && $timestamp > $cutoff
        ));
    }

    /** Nome de arquivo derivado por hash — chaves nunca viram caminho (anti path traversal). */
    private function pathFor(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
