<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Validation\PasswordPolicy;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

/**
 * Caso de uso: atualizar o perfil do usuário autenticado.
 *
 * Atualização parcial: campo null ou vazio significa "manter valor atual".
 * A nova senha, quando informada, passa pela PasswordPolicy e é armazenada
 * como hash bcrypt — nunca em texto puro.
 */
class UpdateUserProfileUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    /**
     * @param int $userId Id vindo da sessão autenticada (não do formulário).
     * @return bool false quando nenhum campo foi informado para atualizar.
     * @throws ValidationException Usuário inválido, e-mail malformado ou
     *                             nova senha fora da política.
     */
    public function execute(int $userId, ?string $name, ?string $email, ?string $newPassword): bool
    {
        if ($userId <= 0) {
            throw new ValidationException('Usuário inválido.');
        }

        $name = $name !== null ? trim($name) : null;
        $email = $email !== null ? trim($email) : null;
        $newPassword = $newPassword !== null ? trim($newPassword) : null;

        if (($email ?? '') !== '' && !filter_var((string) $email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Formato de e-mail inválido.');
        }

        $passwordHash = null;
        if (($newPassword ?? '') !== '') {
            PasswordPolicy::validate((string) $newPassword);
            $passwordHash = password_hash((string) $newPassword, PASSWORD_DEFAULT);
        }

        return $this->userRepository->updateProfile($userId, $name, $email, $passwordHash);
    }
}
