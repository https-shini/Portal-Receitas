<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\RecipeRepositoryInterface;

/**
 * Caso de uso: montar o catálogo de receitas da home, com ou sem filtros.
 *
 * Produz duas projeções da mesma consulta:
 *  - 'cards'   → resumo exibido na grade de resultados;
 *  - 'details' → detalhe completo (modal), com ingredientes e preparo já
 *                normalizados para a view.
 */
class FindRecipesUseCase
{
    /**
     * Rótulos exibidos para idcategoriaFK. Os ids espelham a tabela categoria
     * do seed; alterações lá devem refletir aqui (e nas views que listam as
     * seis categorias fixas).
     */
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

    /**
     * Busca receitas aplicando os filtros informados (null = sem filtro).
     *
     * @return array{cards: list<array<string, mixed>>, details: list<array<string, mixed>>}
     */
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

    /**
     * Regra de negócio da busca explícita: o usuário precisa informar um
     * termo OU selecionar uma categoria — submissão vazia é orientada, não
     * silenciosamente ignorada.
     *
     * @throws ValidationException Mensagem de orientação exibida na home.
     */
    public function validateSearchRequest(?string $search, ?int $categoryId): void
    {
        if (($search === null || trim($search) === '') && $categoryId === null) {
            throw new ValidationException('Tente escrever algo na barra de pesquisa ou selecionar uma categoria');
        }
    }

    /** Projeção de card: renomeia colunas do banco para o vocabulário da view. */
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

    /**
     * Projeção de detalhe.
     *
     * Ingredientes: as 15 colunas ingrediente_N viram lista posicional; vagas
     * vazias recebem o texto legado "Não há mais ingredientes" (a view decide
     * exibi-las ou não).
     *
     * Modo de preparo: o texto é quebrado em passos por ponto final —
     * comportamento herdado do site original. Limitação conhecida: pontos em
     * abreviações/números também quebram; aceito para manter paridade com o
     * conteúdo do seed, escrito para esse formato.
     */
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
