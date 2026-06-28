<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

use App\User\Domain\Contract\UserCollection;
use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findAllPaginated(int $page = 1, int $limit = 20): UserCollection;

    public function findByEmail(string $email): ?User;

    public function existsByEmail(string $email): bool;

    public function save(User $user): void;

    public function remove(User $user): void;
}