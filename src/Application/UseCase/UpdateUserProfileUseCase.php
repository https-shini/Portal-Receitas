<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

class UpdateUserProfileUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

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

        $passwordHash = ($newPassword ?? '') !== '' ? password_hash((string) $newPassword, PASSWORD_DEFAULT) : null;

        return $this->userRepository->updateProfile($userId, $name, $email, $passwordHash);
    }
}
