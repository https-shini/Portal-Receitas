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
 * interpolado no SQL. O nome da categoria vem por LEFT JOIN (`nomeCategoria`),
 * eliminando os rótulos antes fixados em código.
 */
class PdoRecipeRepository implements RecipeRepositoryInterface
{
    private const INGREDIENT_COLUMNS = [
        'ingrediente_1', 'ingrediente_2', 'ingrediente_3', 'ingrediente_4', 'ingrediente_5',
        'ingrediente_6', 'ingrediente_7', 'ingrediente_8', 'ingrediente_9', 'ingrediente_10',
        'ingrediente_11', 'ingrediente_12', 'ingrediente_13', 'ingrediente_14', 'ingrediente_15',
    ];

    /** Colunas de resumo (card). */
    private const SUMMARY_COLUMNS = 'r.idReceita, r.nomeReceita, r.tempoReceita, r.idcategoriaFK, r.imagem, c.nomeCategoria';

    /** Colunas de detalhe (página/modal): tudo do resumo + vídeo, ingredientes, preparo. */
    private const DETAIL_COLUMNS = 'r.idReceita, r.qtdCalorias, r.nomeReceita, r.porcoes, r.tempoReceita, r.link, '
        . 'r.ingrediente_1, r.ingrediente_2, r.ingrediente_3, r.ingrediente_4, r.ingrediente_5, '
        . 'r.ingrediente_6, r.ingrediente_7, r.ingrediente_8, r.ingrediente_9, r.ingrediente_10, '
        . 'r.ingrediente_11, r.ingrediente_12, r.ingrediente_13, r.ingrediente_14, r.ingrediente_15, '
        . 'r.modoPreparo, r.idcategoriaFK, r.imagem, c.nomeCategoria';

    private const FROM_JOIN = ' FROM receita r LEFT JOIN categoria c ON c.idCategoria = r.idcategoriaFK';

    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function findSummaries(?string $search, ?int $categoryId): array
    {
        [$whereSql, $params] = $this->buildFilters($search, $categoryId);
        $sql = 'SELECT ' . self::SUMMARY_COLUMNS . self::FROM_JOIN . $whereSql;

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDetails(?string $search, ?int $categoryId): array
    {
        [$whereSql, $params] = $this->buildFilters($search, $categoryId);
        $sql = 'SELECT ' . self::DETAIL_COLUMNS . self::FROM_JOIN . $whereSql;

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT ' . self::DETAIL_COLUMNS . self::FROM_JOIN . ' WHERE r.idReceita = :id LIMIT 1';

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function findRelated(int $categoryId, int $excludeId, int $limit): array
    {
        // $limit é um inteiro controlado internamente (não vem do usuário);
        // interpolado após cast para evitar a limitação do bind em LIMIT.
        $limit = max(1, min(24, $limit));
        $sql = 'SELECT ' . self::SUMMARY_COLUMNS . self::FROM_JOIN
            . ' WHERE r.idcategoriaFK = :categoryId AND r.idReceita <> :excludeId'
            . ' ORDER BY RAND() LIMIT ' . $limit;

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute(['categoryId' => $categoryId, 'excludeId' => $excludeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Constrói a cláusula WHERE comum às consultas de listagem.
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
            $conditions[] = 'r.idcategoriaFK = :categoryId';
            $params['categoryId'] = $categoryId;
        }

        if ($search !== null && $search !== '') {
            $searchConditions = [];
            $searchValue = '%' . $search . '%';

            foreach (self::INGREDIENT_COLUMNS as $index => $column) {
                $placeholder = 's' . $index;
                $searchConditions[] = sprintf('r.%s LIKE :%s', $column, $placeholder);
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
