<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\RatingRepositoryInterface;

/**
 * Caso de uso: registrar (ou remover) a nota de um usuário para uma receita e
 * devolver o agregado atualizado + a nota do usuário.
 */
class RateRecipeUseCase
{
    public function __construct(private readonly RatingRepositoryInterface $ratingRepository)
    {
    }

    /**
     * @param  int $score Nota de 1 a 5, ou 0 para remover a avaliação.
     * @return array{average: float|null, count: int, userScore: int|null}
     * @throws ValidationException Nota fora do intervalo permitido.
     */
    public function execute(int $userId, int $recipeId, int $score): array
    {
        if ($score === 0) {
            $this->ratingRepository->remove($userId, $recipeId);
        } elseif ($score >= 1 && $score <= 5) {
            $this->ratingRepository->rate($userId, $recipeId, $score);
        } else {
            throw new ValidationException('A nota deve ser de 1 a 5.');
        }

        $aggregate = $this->ratingRepository->aggregate($recipeId);
        $aggregate['userScore'] = $this->ratingRepository->userScore($userId, $recipeId);

        return $aggregate;
    }
}
