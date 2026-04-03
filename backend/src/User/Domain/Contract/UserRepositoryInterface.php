<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    /** @return list<User> */
    public function findAll(): array;

    public function findByEmail(string $email): ?User;

    public function existsByEmail(string $email): bool;

    public function save(User $project): void;

    public function remove(User $project): void;
}