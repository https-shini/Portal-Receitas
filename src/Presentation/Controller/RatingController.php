<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\RateLimiterInterface;
use App\Application\UseCase\RateRecipeUseCase;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\RatingRepositoryInterface;
use App\Presentation\Http\SessionManager;

/**
 * Controller de avaliações: registrar/atualizar/remover a nota do usuário via
 * API (POST autenticado + CSRF + rate limit). Escrita restrita à tabela
 * avaliacao.
 */
class RatingController
{
    private const MAX = 30;
    private const WINDOW_SECONDS = 60;

    public function __construct(
        private readonly RateRecipeUseCase $rateRecipeUseCase,
        private readonly RatingRepositoryInterface $ratingRepository,
        private readonly SessionManager $sessionManager,
        private readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    /** Nota atual do usuário da sessão para a receita (usado ao montar a página). */
    public function userScore(int $recipeId): ?int
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        if (empty($this->sessionManager->get('logado')) || $userId < 1) {
            return null;
        }

        return $this->ratingRepository->userScore($userId, $recipeId);
    }

    /**
     * POST /api/ratings.php — grava a nota (1–5) ou a remove (nota 0).
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function rate(array $input): array
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        if (empty($this->sessionManager->get('logado')) || $userId < 1) {
            return ['status' => 401, 'body' => ['detail' => 'Você precisa entrar para avaliar receitas.']];
        }

        $csrf = isset($input['_csrf']) ? (string) $input['_csrf'] : null;
        if (!$this->sessionManager->validateCsrf($csrf)) {
            return ['status' => 403, 'body' => ['detail' => 'Requisição inválida. Recarregue a página e tente novamente.']];
        }

        $rateKey = 'rating|' . $userId;
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::MAX, self::WINDOW_SECONDS)) {
            return ['status' => 429, 'body' => ['detail' => 'Muitas ações em pouco tempo. Aguarde um instante.']];
        }
        $this->rateLimiter->hit($rateKey, self::WINDOW_SECONDS);

        $recipeId = (int) ($input['idReceita'] ?? 0);
        if ($recipeId < 1) {
            return ['status' => 400, 'body' => ['detail' => 'Receita inválida.']];
        }

        try {
            $result = $this->rateRecipeUseCase->execute($userId, $recipeId, (int) ($input['nota'] ?? 0));
        } catch (ValidationException $exception) {
            return ['status' => 400, 'body' => ['detail' => $exception->getMessage()]];
        }

        return ['status' => 200, 'body' => $result];
    }
}
