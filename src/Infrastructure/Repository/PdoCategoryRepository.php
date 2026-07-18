<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\CategoryRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de categorias.
 *
 * O total por categoria vem de um LEFT JOIN agregado, de modo que categorias
 * sem receitas ainda aparecem (total 0) — a interface decide se as exibe.
 */
class PdoCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function findAllWithCounts(): array
    {
        $sql = 'SELECT c.idCategoria, c.nomeCategoria, c.icone, COUNT(r.idReceita) AS total'
            . ' FROM categoria c'
            . ' LEFT JOIN receita r ON r.idcategoriaFK = c.idCategoria'
            . ' GROUP BY c.idCategoria, c.nomeCategoria, c.icone'
            . ' ORDER BY total DESC, c.nomeCategoria ASC';

        $stmt = $this->connectionFactory->create()->query($sql);

        $categories = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categories[] = [
                'idCategoria' => (int) $row['idCategoria'],
                'nomeCategoria' => (string) $row['nomeCategoria'],
                'icone' => $row['icone'] !== null ? (string) $row['icone'] : null,
                'total' => (int) $row['total'],
            ];
        }

        return $categories;
    }
}
