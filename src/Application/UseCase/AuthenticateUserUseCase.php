<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

class AuthenticateUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function execute(string $email, string $password): array
    {
        $email = trim($email);
        $password = trim($password);

        if ($email === '' || $password === '') {
            throw new ValidationException('E-mail e senha são obrigatórios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Formato de e-mail inválido.');
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !password_verify($password, $user['senhaUsuario'])) {
            throw new AuthenticationException('Senha ou e-mail incorretos.');
        }

        return $user;
    }
}
