<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\CommentRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de comentários. A remoção só
 * afeta o comentário do próprio usuário (WHERE idUsuario), garantindo que
 * ninguém apague comentário alheio.
 */
class PdoCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function add(int $userId, int $recipeId, string $text): int
    {
        $pdo = $this->connectionFactory->create();
        $stmt = $pdo->prepare(
            'INSERT INTO comentario (idReceita, idUsuario, texto) VALUES (:r, :u, :t)',
        );
        $stmt->execute(['r' => $recipeId, 'u' => $userId, 't' => $text]);

        return (int) $pdo->lastInsertId();
    }

    public function delete(int $commentId, int $userId): bool
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'DELETE FROM comentario WHERE idComentario = :c AND idUsuario = :u',
        );
        $stmt->execute(['c' => $commentId, 'u' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function listByRecipe(int $recipeId): array
    {
        $sql = 'SELECT c.idComentario, c.idUsuario, u.nomeUsuario AS autor, c.texto, c.criadoEm'
            . ' FROM comentario c'
            . ' JOIN usuario u ON u.idUsuario = c.idUsuario'
            . ' WHERE c.idReceita = :r'
            . ' ORDER BY c.criadoEm DESC, c.idComentario DESC';

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute(['r' => $recipeId]);

        $comments = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $comments[] = [
                'idComentario' => (int) $row['idComentario'],
                'idUsuario' => (int) $row['idUsuario'],
                'autor' => (string) $row['autor'],
                'texto' => (string) $row['texto'],
                'criadoEm' => (string) $row['criadoEm'],
            ];
        }

        return $comments;
    }
}
