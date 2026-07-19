<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Application\Query\RecipeQuery;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Fake em memória do repositório de receitas. Reproduz o contrato observável
 * da implementação PDO (linhas associativas com as colunas do banco e o
 * nomeCategoria do JOIN), permitindo testar os casos de uso sem banco.
 */
class InMemoryRecipeRepository implements RecipeRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /**
     * Pré-carrega uma receita no formato de linha do banco (arrange).
     *
     * @param array<string, mixed> $overrides
     */
    public function seed(int $id, string $name, int $categoryId, string $categoryName, array $overrides = []): void
    {
        $this->rows[$id] = array_merge([
            'idReceita' => $id,
            'nomeReceita' => $name,
            'tempoReceita' => '30 min',
            'dificuldade' => 'Médio',
            'idcategoriaFK' => $categoryId,
            'imagem' => 'x.png',
            'nomeCategoria' => $categoryName,
            // colunas de detalhe
            'qtdCalorias' => 100.0,
            'porcoes' => 4,
            'link' => '<iframe src="https://www.youtube-nocookie.com/embed/x"></iframe>',
            'tempoCozimento' => '15 min',
            'dicas' => null,
            'modoPreparo' => 'Passo um. Passo dois.',
        ], $overrides);
    }

    public function search(RecipeQuery $query): array
    {
        $matched = array_values(array_filter($this->rows, fn (array $r) => $this->matches($r, $query)));
        $offset = ($query->page - 1) * $query->perPage;

        return array_slice($matched, $offset, $query->perPage);
    }

    public function count(RecipeQuery $query): int
    {
        return count(array_filter($this->rows, fn (array $r) => $this->matches($r, $query)));
    }

    public function findById(int $id): ?array
    {
        return $this->rows[$id] ?? null;
    }

    public function findRelated(int $categoryId, int $excludeId, int $limit): array
    {
        $related = array_filter(
            $this->rows,
            fn (array $r) => (int) $r['idcategoriaFK'] === $categoryId && (int) $r['idReceita'] !== $excludeId,
        );

        return array_slice(array_values($related), 0, $limit);
    }

    /** @var array<int, list<string>> */
    private array $images = [];

    public function withImages(int $recipeId, array $files): void
    {
        $this->images[$recipeId] = $files;
    }

    public function findImages(int $recipeId): array
    {
        return $this->images[$recipeId] ?? [];
    }

    private function matches(array $row, RecipeQuery $query): bool
    {
        if ($query->categoryIds !== [] && !in_array((int) $row['idcategoriaFK'], $query->categoryIds, true)) {
            return false;
        }

        if ($query->difficulties !== [] && !in_array((string) $row['dificuldade'], $query->difficulties, true)) {
            return false;
        }

        if ($query->search !== null && stripos((string) $row['nomeReceita'], $query->search) === false) {
            return false;
        }

        return true;
    }
}
