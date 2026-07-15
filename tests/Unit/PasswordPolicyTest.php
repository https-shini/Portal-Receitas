<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Validation\PasswordPolicy;
use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function testAcceptsPasswordWithMinLengthLetterAndNumber(): void
    {
        PasswordPolicy::validate('receita123');
        PasswordPolicy::validate('Abcdefg1');

        $this->addToAssertionCount(2);
    }

    public static function weakPasswords(): array
    {
        return [
            'curta' => ['abc1'],
            'sem número' => ['somenteletras'],
            'sem letra' => ['12345678'],
            'vazia' => [''],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function testRejectsWeakPasswords(string $password): void
    {
        $this->expectException(ValidationException::class);
        PasswordPolicy::validate($password);
    }
}
