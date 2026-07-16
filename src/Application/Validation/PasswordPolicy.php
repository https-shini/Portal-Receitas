<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Exception\ValidationException;

/**
 * Política de senha do sistema — fonte única da regra, aplicada tanto no
 * cadastro quanto na troca de senha do perfil.
 *
 * Regra de negócio (referência: AuthService): mínimo de 8 caracteres,
 * contendo ao menos uma letra e ao menos um número. A verificação de letra
 * usa \p{L} (Unicode) para aceitar alfabetos acentuados.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /**
     * Valida a senha em texto puro antes do hash.
     *
     * @throws ValidationException Com mensagem específica do requisito
     *                             violado, apta a exibição ao usuário.
     */
    public static function validate(string $password): void
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new ValidationException(sprintf('A senha deve ter no mínimo %d caracteres.', self::MIN_LENGTH));
        }

        if (preg_match('/[0-9]/', $password) !== 1) {
            throw new ValidationException('A senha deve conter pelo menos um número.');
        }

        if (preg_match('/\p{L}/u', $password) !== 1) {
            throw new ValidationException('A senha deve conter pelo menos uma letra.');
        }
    }
}
