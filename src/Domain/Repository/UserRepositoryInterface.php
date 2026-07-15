<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;

    public function create(string $name, string $email, string $passwordHash, int $favoriteCategoryId): bool;

    public function updateProfile(int $userId, ?string $name, ?string $email, ?string $passwordHash): bool;
}
