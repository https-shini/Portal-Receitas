<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\CommentRepositoryInterface;

/**
 * Caso de uso: publicar um comentário em uma receita. Valida o texto
 * (não vazio, no máximo 500 caracteres) e devolve o id criado.
 */
class PostCommentUseCase
{
    private const MAX = 500;

    public function __construct(private readonly CommentRepositoryInterface $commentRepository)
    {
    }

    /**
     * @throws ValidationException Texto vazio ou longo demais.
     */
    public function execute(int $userId, int $recipeId, string $text): int
    {
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
