<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\RateLimiterInterface;
use App\Application\UseCase\ListFavoritesUseCase;
use App\Application\UseCase\ToggleFavoriteUseCase;
use App\Domain\Repository\FavoriteRepositoryInterface;
use App\Presentation\Http\SessionManager;

/**
 * Controller de favoritos: alternância via API (POST autenticado + CSRF +
 * rate limit) e dados da página /favoritas. Escrita restrita à tabela
 * favorito (menor privilégio no banco).
 */
class FavoriteController
{
    private const TOGGLE_MAX = 60;
    private const TOGGLE_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly ToggleFavoriteUseCase $toggleFavoriteUseCase,
        private readonly ListFavoritesUseCase $listFavoritesUseCase,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly SessionManager $sessionManager,
        private readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    /**
     * POST /api/favorites.php — alterna o favorito da receita para o usuário
     * autenticado. Exige sessão + token CSRF; limita a frequência por usuário.
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function toggle(array $input): array
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        if (empty($this->sessionManager->get('logado')) || $userId < 1) {
            return ['status' => 401, 'body' => ['detail' => 'Você precisa entrar para favoritar receitas.']];
        }

        $csrf = isset($input['_csrf']) ? (string) $input['_csrf'] : null;
        if (!$this->sessionManager->validateCsrf($csrf)) {
            return ['status' => 403, 'body' => ['detail' => 'Requisição inválida. Recarregue a página e tente novamente.']];
        }

        $rateKey = 'favorite|' . $userId;
        if ($this->rateLimiter->tooManyAttempts($rateKey, self::TOGGLE_MAX, self::TOGGLE_WINDOW_SECONDS)) {
            return ['status' => 429, 'body' => ['detail' => 'Muitas ações em pouco tempo. Aguarde um instante.']];
        }
        $this->rateLimiter->hit($rateKey, self::TOGGLE_WINDOW_SECONDS);

        $recipeId = (int) ($input['idReceita'] ?? 0);
        if ($recipeId < 1) {
            return ['status' => 400, 'body' => ['detail' => 'Receita inválida.']];
        }

        $favorited = $this->toggleFavoriteUseCase->execute($userId, $recipeId);

        return ['status' => 200, 'body' => ['favorited' => $favorited]];
    }

    /** Estado atual de favorito para a sessão (usado ao montar a página). */
    public function isFavorite(int $recipeId): bool
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        if (empty($this->sessionManager->get('logado')) || $userId < 1) {
            return false;
        }

        return $this->favoriteRepository->exists($userId, $recipeId);
    }

    /**
     * Dados da página /favoritas do usuário autenticado.
     *
     * @return array{cards: list<array<string, mixed>>}
     */
    public function favorites(): array
    {
        $this->sessionManager->start();
        $userId = (int) $this->sessionManager->get('idUsuario');

        return ['cards' => $this->listFavoritesUseCase->execute($userId)];
    }
}
