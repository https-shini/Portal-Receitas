<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de persistência dos comentários de receitas. O usuário só remove os
 * próprios comentários; nunca há edição de comentário alheio.
 */
interface CommentRepositoryInterface
{
    /** Cria um comentário e devolve o id gerado. */
    public function add(int $userId, int $recipeId, string $text): int;

    /** Remove o comentário se pertencer ao usuário; devolve true se removeu. */
    public function delete(int $commentId, int $userId): bool;

    /**
     * Comentários de uma receita, mais recentes primeiro, com o nome do autor.
     *
     * @return list<array{idComentario:int, idUsuario:int, autor:string, texto:string, criadoEm:string}>
     */
    public function listByRecipe(int $recipeId): array;
}
