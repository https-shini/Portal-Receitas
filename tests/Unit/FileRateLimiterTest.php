<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Infrastructure\Security\FileRateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Janela deslizante do rate limiter: bloqueia no limite, não antes, e o
 * clear() reabilita imediatamente (sucesso de login não pune o titular).
 */
class FileRateLimiterTest extends TestCase
{
    private FileRateLimiter $limiter;

    private string $key;

    protected function setUp(): void
    {
        $this->limiter = new FileRateLimiter(sys_get_temp_dir() . '/hmg-ratelimit-test');
        $this->key = 'teste|' . uniqid('', true);
    }

    public function testBlocksOnlyAfterReachingTheLimit(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->assertFalse($this->limiter->tooManyAttempts($this->key, 5, 60));
            $this->limiter->hit($this->key, 60);
        }

        $this->assertFalse($this->limiter->tooManyAttempts($this->key, 5, 60), '4 falhas ainda não bloqueiam.');

        $this->limiter->hit($this->key, 60);
        $this->assertTrue($this->limiter->tooManyAttempts($this->key, 5, 60), 'A 5ª falha atinge o limite.');
    }

    public function testClearResetsTheCounter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->hit($this->key, 60);
        }
        $this->assertTrue($this->limiter->tooManyAttempts($this->key, 5, 60));

        $this->limiter->clear($this->key);
        $this->assertFalse($this->limiter->tooManyAttempts($this->key, 5, 60));
    }
}
