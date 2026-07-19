<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\RatingRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de avaliações.
 *
 * rate() faz upsert (INSERT ... ON DUPLICATE KEY UPDATE) contra a PK composta
 * (idUsuario, idReceita): a primeira nota cria a linha, o revoto atualiza.
 */
class PdoRatingRepository implements RatingRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function rate(int $userId, int $recipeId, int $score): void
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'INSERT INTO avaliacao (idUsuario, idReceita, nota) VALUES (:u, :r, :n)'
            . ' ON DUPLICATE KEY UPDATE nota = VALUES(nota)',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId, 'n' => $score]);
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'DELETE FROM avaliacao WHERE idUsuario = :u AND idReceita = :r',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId]);
    }

    public function userScore(int $userId, int $recipeId): ?int
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'SELECT nota FROM avaliacao WHERE idUsuario = :u AND idReceita = :r LIMIT 1',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId]);
        $nota = $stmt->fetchColumn();

        return $nota === false ? null : (int) $nota;
    }

    public function aggregate(int $recipeId): array
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'SELECT AVG(nota) AS media, COUNT(*) AS total FROM avaliacao WHERE idReceita = :r',
        );
        $stmt->execute(['r' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['media' => null, 'total' => 0];

        $count = (int) $row['total'];

        return [
            'average' => $count > 0 && $row['media'] !== null ? round((float) $row['media'], 1) : null,
            'count' => $count,
        ];
    }
}
