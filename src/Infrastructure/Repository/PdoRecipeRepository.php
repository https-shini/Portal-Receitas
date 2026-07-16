<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\RecipeRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de receitas.
 *
 * A busca por ingrediente percorre as 15 colunas ingrediente_N com LIKE
 * (modelo de dados herdado do TCC original — ver ADR-003 em docs/backend.md).
 * Cada coluna recebe seu próprio placeholder (:s0..:s14); o termo nunca é
 * interpolado no SQL.
 */
class PdoRecipeRepository implements RecipeRepositoryInterface
{
    private const INGREDIENT_COLUMNS = [
        'ingrediente_1', 'ingrediente_2', 'ingrediente_3', 'ingrediente_4', 'ingrediente_5',
        'ingrediente_6', 'ingrediente_7', 'ingrediente_8', 'ingrediente_9', 'ingrediente_10',
        'ingrediente_11', 'ingrediente_12', 'ingrediente_13', 'ingrediente_14', 'ingrediente_15',
    ];

    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function findSummaries(?string $search, ?int $categoryId): array
    {
        $query = 'SELECT idReceita, nomeReceita, tempoReceita, idcategoriaFK, imagem FROM receita';
        [$whereSql, $params] = $this->buildFilters($search, $categoryId);

        $stmt = $this->connectionFactory->create()->prepare($query . $whereSql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDetails(?string $search, ?int $categoryId): array
    {
        $query = 'SELECT idReceita, qtdCalorias, nomeReceita, porcoes, tempoReceita, link, ingrediente_1, ingrediente_2, ingrediente_3, ingrediente_4, ingrediente_5, ingrediente_6, ingrediente_7, ingrediente_8, ingrediente_9, ingrediente_10, ingrediente_11, ingrediente_12, ingrediente_13, ingrediente_14, ingrediente_15, modoPreparo, idcategoriaFK FROM receita';
        [$whereSql, $params] = $this->buildFilters($search, $categoryId);

        $stmt = $this->connectionFactory->create()->prepare($query . $whereSql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Constrói a cláusula WHERE comum às duas consultas.
     *
     * Filtros são combináveis com AND: categoria exata e/ou termo presente em
     * QUALQUER uma das 15 colunas de ingrediente (grupo de ORs). A cláusula é
     * montada apenas com fragmentos fixos; valores ficam em $params.
     *
     * @return array{0: string, 1: array<string, mixed>} SQL do WHERE (ou '')
     *                                                    e parâmetros do bind.
     */
    private function buildFilters(?string $search, ?int $categoryId): array
    {
        $conditions = [];
        $params = [];

        if ($categoryId !== null) {
            $conditions[] = 'idcategoriaFK = :categoryId';
            $params['categoryId'] = $categoryId;
        }

        if ($search !== null && $search !== '') {
            $searchConditions = [];
            $searchValue = '%' . $search . '%';

            foreach (self::INGREDIENT_COLUMNS as $index => $column) {
                $placeholder = 's' . $index;
                $searchConditions[] = sprintf('%s LIKE :%s', $column, $placeholder);
                $params[$placeholder] = $searchValue;
            }

            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }

        if ($conditions === []) {
            return ['', []];
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
