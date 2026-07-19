<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Repository\CommentRepositoryInterface;

/** Fake em memória do repositório de comentários. */
class InMemoryCommentRepository implements CommentRepositoryInterface
{
    /** @var array<int, array{idComentario:int, idReceita:int, idUsuario:int, texto:string}> */
    private array $rows = [];

    private int $nextId = 1;

    public function add(int $userId, int $recipeId, string $text): int
    {
        $id = $this->nextId++;
        $this->rows[$id] = ['idComentario' => $id, 'idReceita' => $recipeId, 'idUsuario' => $userId, 'texto' => $text];

        return $id;
    }

    public function delete(int $commentId, int $userId): bool
    {
        if (isset($this->rows[$commentId]) && $this->rows[$commentId]['idUsuario'] === $userId) {
            unset($this->rows[$commentId]);

            return true;
        }

        return false;
    }

    public function listByRecipe(int $recipeId): array
    {
        $out = [];
        foreach (array_reverse($this->rows) as $row) {
            if ($row['idReceita'] === $recipeId) {
                $out[] = [
                    'idComentario' => $row['idComentario'],
                    'idUsuario' => $row['idUsuario'],
                    'autor' => 'User ' . $row['idUsuario'],
                    'texto' => $row['texto'],
                    'criadoEm' => '2026-07-19 12:00:00',
                ];
            }
        }

        return $out;
    }
}
