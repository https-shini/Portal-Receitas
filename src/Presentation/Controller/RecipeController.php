<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\FindRecipesUseCase;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\SessionManager;

class RecipeController
{
    public function __construct(
        private readonly FindRecipesUseCase $findRecipesUseCase,
        private readonly SessionManager $sessionManager,
    ) {
    }

    public function list(array $query): array
    {
        $this->sessionManager->start();

        $search = isset($query['pesquisa']) ? trim((string) $query['pesquisa']) : null;
        $categoryId = isset($query['categoriaReceita']) && $query['categoriaReceita'] !== '' ? (int) $query['categoriaReceita'] : null;
        $errorMessage = null;

        if (isset($query['buscar'])) {
            try {
                $this->findRecipesUseCase->validateSearchRequest($search, $categoryId);
            } catch (ValidationException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        $recipes = $this->findRecipesUseCase->execute(
            isset($query['buscar']) ? $search : null,
            isset($query['buscar']) ? $categoryId : null,
        );

        if (isset($query['buscar']) && $errorMessage === null && $recipes['cards'] === []) {
            $errorMessage = 'Não foi possível encontrar receitas :(';
        }

        return [
            'cards' => $recipes['cards'],
            'details' => $recipes['details'],
            'errorMessage' => $errorMessage,
        ];
    }
}
