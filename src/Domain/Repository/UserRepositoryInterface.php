<?php

declare(strict_types=1);

namespace App\Domain\Repository;

/**
 * Contrato de persistência de contas de usuário.
 *
 * O domínio depende apenas desta abstração; a implementação concreta
 * (PDO/MySQL) é injetada no composition root (config/bootstrap.php).
 */
interface UserRepositoryInterface
{
    /**
     * Localiza um usuário pelo e-mail exato.
     *
     * @return array<string, mixed>|null Linha da tabela usuario (idUsuario,
     *                                   nomeUsuario, emailUsuario, senhaUsuario,
     *                                   idCategoriaFK) ou null quando o e-mail
     *                                   não está cadastrado.
     */
    public function findByEmail(string $email): ?array;

    /**
     * Persiste uma nova conta.
     *
     * Pré-condição: $passwordHash já deve ser um hash bcrypt — este contrato
     * nunca recebe senha em texto puro.
     */
    public function create(string $name, string $email, string $passwordHash, int $favoriteCategoryId): bool;

    /**
     * Atualização parcial do perfil: apenas campos não nulos e não vazios são
     * alterados (null/'' significa "manter o valor atual").
     *
     * @param string|null $passwordHash Hash bcrypt da nova senha, ou null para
     *                                  manter a senha atual.
     * @return bool false quando nenhum campo foi informado para atualizar.
     */
    public function updateProfile(int $userId, ?string $name, ?string $email, ?string $passwordHash): bool;
}
