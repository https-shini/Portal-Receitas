<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\RecipeRepositoryInterface;

class FindRecipesUseCase
{
    private const CATEGORY_LABELS = [
        1 => 'Frutos do Mar',
        2 => 'Massas',
        3 => 'Veganas',
        4 => 'Salgados',
        5 => 'Doces',
        6 => 'Carnes',
    ];

    public function __construct(private readonly RecipeRepositoryInterface $recipeRepository)
    {
    }

    public function execute(?string $search, ?int $categoryId): array
    {
        $search = $search !== null ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }

        if ($categoryId !== null && $categoryId < 1) {
            $categoryId = null;
        }

        $summaries = $this->recipeRepository->findSummaries($search, $categoryId);
        $details = $this->recipeRepository->findDetails($search, $categoryId);

        return [
            'cards' => array_map(fn (array $recipe) => $this->mapSummary($recipe), $summaries),
            'details' => array_map(fn (array $recipe) => $this->mapDetail($recipe), $details),
        ];
    }

    public function validateSearchRequest(?string $search, ?int $categoryId): void
    {
        if (($search === null || trim($search) === '') && $categoryId === null) {
            throw new ValidationException('Tente escrever algo na barra de pesquisa ou selecionar uma categoria');
        }
    }

    private function mapSummary(array $recipe): array
    {
        return [
            'id' => (int) $recipe['idReceita'],
            'name' => $recipe['nomeReceita'],
            'time' => $recipe['tempoReceita'],
            'category' => $this->categoryLabel((int) $recipe['idcategoriaFK']),
            'image' => $recipe['imagem'],
        ];
    }

    private function mapDetail(array $recipe): array
    {
        $ingredients = [];
        for ($index = 1; $index <= 15; $index++) {
            $column = 'ingrediente_' . $index;
            $value = trim((string) ($recipe[$column] ?? ''));
            $ingredients[] = $value !== '' ? $value : 'Não há mais ingredientes';
        }

        $preparation = [];
        $steps = explode('.', (string) $recipe['modoPreparo']);
        foreach ($steps as $index => $step) {
            $step = trim($step);
            if ($step === '') {
                continue;
            }
            $preparation[] = sprintf('%d. %s', $index, $step);
        }

        return [
            'id' => (int) $recipe['idReceita'],
            'name' => $recipe['nomeReceita'],
            'video' => $recipe['link'],
            'category' => $this->categoryLabel((int) $recipe['idcategoriaFK']),
            'time' => $recipe['tempoReceita'],
            'servings' => (int) $recipe['porcoes'],
            'calories' => (float) $recipe['qtdCalorias'],
            'ingredients' => $ingredients,
            'preparation' => $preparation,
        ];
    }

    private function categoryLabel(int $id): string
    {
        return self::CATEGORY_LABELS[$id] ?? 'Sem categoria';
    }
}
