<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Validation\PasswordPolicy;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;

/**
 * Caso de uso: cadastrar um novo usuário.
 *
 * Regras de negócio aplicadas, nesta ordem:
 *  1. nome, e-mail, senha e categoria favorita são obrigatórios;
 *  2. e-mail deve ter formato válido;
 *  3. senha deve cumprir a PasswordPolicy;
 *  4. e-mail deve ser inédito (a UNIQUE do banco é a garantia final sob
 *     concorrência — esta pré-checagem existe para dar mensagem amigável).
 *
 * Efeito colateral: a senha é convertida em hash bcrypt (PASSWORD_DEFAULT)
 * antes de tocar o repositório — texto puro nunca sai deste método.
 */
class RegisterUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    /**
     * @param int|null $favoriteCategoryId Id de categoria (1..6); null ou < 1
     *                                     é rejeitado como cadastro inválido.
     * @throws ValidationException Quando qualquer regra acima é violada.
     */
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
