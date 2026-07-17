<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

/**
 * Caso de uso: autenticar um usuário por e-mail e senha.
 *
 * A comparação usa password_verify contra o hash bcrypt armazenado.
 * Regra de negócio: "usuário inexistente" e "senha errada" produzem a MESMA
 * exceção, impedindo enumeração de contas cadastradas.
 */
class AuthenticateUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    /**
     * @return array<string, mixed> Linha do usuário autenticado (inclui o
     *                              hash em senhaUsuario — o chamador decide o
     *                              que expor; a sessão guarda apenas id, nome
     *                              e e-mail).
     * @throws ValidationException     Campos vazios ou e-mail malformado.
     * @throws AuthenticationException Credenciais não conferem.
     */
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

        // Migração transparente de custo do bcrypt: se o hash armazenado foi
        // gerado com parâmetros antigos, regrava com os atuais aproveitando a
        // única ocasião em que a senha em claro está disponível.
        if (password_needs_rehash($user['senhaUsuario'], PASSWORD_DEFAULT)) {
            $this->userRepository->updateProfile(
                (int) $user['idUsuario'],
                null,
                null,
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        return $user;
    }
}
