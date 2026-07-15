<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Validation\PasswordPolicy;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

class RegisterUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function execute(string $name, string $email, string $password, ?int $favoriteCategoryId): bool
    {
        $name = trim($name);
        $email = trim($email);
        $password = trim($password);
        $favoriteCategoryId = $favoriteCategoryId ?? 0;

        if ($name === '' || $email === '' || $password === '' || $favoriteCategoryId < 1) {
            throw new ValidationException('Dados de cadastro inválidos.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Formato de e-mail inválido.');
        }

        PasswordPolicy::validate($password);

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new ValidationException('E-mail já existente, por favor digite outro!');
        }

        return $this->userRepository->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $favoriteCategoryId);
    }
}
