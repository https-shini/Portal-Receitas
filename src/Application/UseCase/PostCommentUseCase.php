<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\CommentRepositoryInterface;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso: publicar um comentário em uma receita. Valida o texto
 * (não vazio, no máximo 500 caracteres) e devolve o id criado.
 */
class PostCommentUseCase
{
    private const MAX = 500;

    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly RecipeRepositoryInterface $recipeRepository,
    ) {
    }

    /**
     * @throws ValidationException Texto vazio ou longo demais.
     * @throws NotFoundException   Receita inexistente — sem esta guarda a
     *                             violação de chave estrangeira subiria como
     *                             PDOException e viraria um 503 enganoso.
     */
    public function execute(int $userId, int $recipeId, string $text): int
    {
        if (!$this->recipeRepository->exists($recipeId)) {
            throw new NotFoundException('Receita não encontrada.');
        }

        $text = trim($text);

        if ($text === '') {
            throw new ValidationException('Escreva algo antes de enviar.');
        }
        if (mb_strlen($text) > self::MAX) {
            throw new ValidationException('O comentário deve ter no máximo ' . self::MAX . ' caracteres.');
        }

        return $this->commentRepository->add($userId, $recipeId, $text);
    }
}
