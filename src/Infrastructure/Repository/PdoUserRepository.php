<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Database\PdoConnectionFactory;
use PDO;

/**
 * Implementação MySQL/MariaDB do repositório de usuários.
 *
 * Toda consulta usa prepared statements com placeholders nomeados — entrada
 * do usuário jamais é concatenada em SQL.
 */
class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PdoConnectionFactory $connectionFactory)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'SELECT idUsuario, nomeUsuario, emailUsuario, senhaUsuario, idCategoriaFK FROM usuario WHERE emailUsuario = :email LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, int $favoriteCategoryId): bool
    {
        $stmt = $this->connectionFactory->create()->prepare(
            'INSERT INTO usuario (nomeUsuario, emailUsuario, senhaUsuario, idCategoriaFK) VALUES (:name, :email, :password, :categoryId)'
        );

        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'categoryId' => $favoriteCategoryId,
        ]);
    }

    /**
     * Monta o UPDATE dinamicamente apenas com os campos informados; o SQL é
     * composto de fragmentos fixos e placeholders — os valores continuam
     * parametrizados. Sem nenhum campo, devolve false sem tocar o banco.
     */
    public function updateProfile(int $userId, ?string $name, ?string $email, ?string $passwordHash): bool
    {
        $fields = [];
        $params = ['id' => $userId];

        if ($name !== null && $name !== '') {
            $fields[] = 'nomeUsuario = :name';
            $params['name'] = $name;
        }

        if ($email !== null && $email !== '') {
            $fields[] = 'emailUsuario = :email';
            $params['email'] = $email;
        }

        if ($passwordHash !== null && $passwordHash !== '') {
            $fields[] = 'senhaUsuario = :password';
            $params['password'] = $passwordHash;
        }

        if ($fields === []) {
            return false;
        }

        $sql = sprintf('UPDATE usuario SET %s WHERE idUsuario = :id', implode(', ', $fields));
        $stmt = $this->connectionFactory->create()->prepare($sql);

        return $stmt->execute($params);
    }
}
