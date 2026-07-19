<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\FavoriteRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de favoritos.
 *
 * add() usa INSERT IGNORE para ser idempotente contra a PK composta
 * (idUsuario, idReceita); listByUser() devolve as receitas no mesmo formato de
 * resumo do catálogo, para reaproveitar o card e o RecipeViewMapper.
 */
class PdoFavoriteRepository implements FavoriteRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function add(int $userId, int $recipeId): void
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'INSERT IGNORE INTO favorito (idUsuario, idReceita) VALUES (:u, :r)',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId]);
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'DELETE FROM favorito WHERE idUsuario = :u AND idReceita = :r',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId]);
    }

    public function exists(int $userId, int $recipeId): bool
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'SELECT 1 FROM favorito WHERE idUsuario = :u AND idReceita = :r LIMIT 1',
        );
        $stmt->execute(['u' => $userId, 'r' => $recipeId]);

        return $stmt->fetchColumn() !== false;
    }

    public function listByUser(int $userId): array
    {
        $sql = 'SELECT r.idReceita, r.nomeReceita, r.tempoReceita, r.dificuldade, r.idcategoriaFK, r.imagem, c.nomeCategoria, a.notaMedia, a.notaTotal'
            . ' FROM favorito f'
            . ' JOIN receita r ON r.idReceita = f.idReceita'
            . ' LEFT JOIN categoria c ON c.idCategoria = r.idcategoriaFK'
            . ' LEFT JOIN (SELECT idReceita, AVG(nota) AS notaMedia, COUNT(*) AS notaTotal FROM avaliacao GROUP BY idReceita) a ON a.idReceita = r.idReceita'
            . ' WHERE f.idUsuario = :u'
            . ' ORDER BY f.criadoEm DESC';

        $stmt = $this->connectionFactory->create()->prepare($sql);
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
