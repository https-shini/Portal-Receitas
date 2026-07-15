<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Exception\ValidationException;

/**
 * Política de senha (referência: AuthService) — mínimo 8 caracteres,
 * pelo menos uma letra e pelo menos um número.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

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
