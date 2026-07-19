<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Repository\RatingRepositoryInterface;

/**
 * Fake em memória do repositório de avaliações. rate() é upsert (chave
 * composta usuário/receita), espelhando o ON DUPLICATE KEY UPDATE do PDO.
 */
class InMemoryRatingRepository implements RatingRepositoryInterface
{
    /** @var array<string, int> chave "user:recipe" → nota */
    private array $scores = [];

    private function key(int $userId, int $recipeId): string
    {
        return $userId . ':' . $recipeId;
    }

    public function rate(int $userId, int $recipeId, int $score): void
    {
        $this->scores[$this->key($userId, $recipeId)] = $score;
    }

    public function remove(int $userId, int $recipeId): void
    {
        unset($this->scores[$this->key($userId, $recipeId)]);
    }

    public function userScore(int $userId, int $recipeId): ?int
    {
        return $this->scores[$this->key($userId, $recipeId)] ?? null;
    }

    public function aggregate(int $recipeId): array
    {
        $notas = [];
        foreach ($this->scores as $chave => $nota) {
            [, $rid] = explode(':', $chave);
            if ((int) $rid === $recipeId) {
                $notas[] = $nota;
            }
        }

        $count = count($notas);

        return [
            'average' => $count > 0 ? round(array_sum($notas) / $count, 1) : null,
            'count' => $count,
        ];
    }
}
