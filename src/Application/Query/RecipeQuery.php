<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Critério de consulta do catálogo (busca facetada + ordenação + paginação).
 * Objeto imutável construído a partir da query string, com valores já
 * validados — a camada de dados confia nos campos (sort é chave de whitelist,
 * ids são inteiros, paginação é positiva).
 */
final class RecipeQuery
{
    /** Chaves de ordenação aceitas (traduzidas para SQL no repositório). */
    public const SORTS = ['relevancia', 'nome', 'tempo'];

    public const PER_PAGE = 12;

    /**
     * @param list<int> $categoryIds
     */
    public function __construct(
        public readonly ?string $search = null,
        public readonly array $categoryIds = [],
        public readonly string $sort = 'relevancia',
        public readonly int $page = 1,
        public readonly int $perPage = self::PER_PAGE,
    ) {
    }

    /**
     * Monta o critério a partir dos parâmetros GET, saneando cada campo.
     * Aceita categoriaReceita como escalar (compat.) ou array (multi-seleção).
     */
    public static function fromArray(array $query): self
    {
        $search = isset($query['pesquisa']) ? trim((string) $query['pesquisa']) : null;
        if ($search === '') {
            $search = null;
        }

        $rawCats = $query['categoriaReceita'] ?? [];
        if (!is_array($rawCats)) {
            $rawCats = [$rawCats];
        }
        $categoryIds = [];
        foreach ($rawCats as $value) {
            $id = (int) $value;
            if ($id > 0 && !in_array($id, $categoryIds, true)) {
                $categoryIds[] = $id;
            }
        }

        $sort = (string) ($query['ordenar'] ?? 'relevancia');
        if (!in_array($sort, self::SORTS, true)) {
            $sort = 'relevancia';
        }

        $page = max(1, (int) ($query['pagina'] ?? 1));

        return new self($search, $categoryIds, $sort, $page);
    }

    /** Há algum filtro (busca ou categoria) ativo? */
    public function hasFilters(): bool
    {
        return $this->search !== null || $this->categoryIds !== [];
    }
}
