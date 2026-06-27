<?php

declare(strict_types=1);

namespace App\User\Domain\Contract;

use App\User\Domain\Entity\ResetPasswordRequest;

interface PasswordResetRequestRepositoryInterface
{
    public function save(ResetPasswordRequest $request): void;

    public function findByTokenHash(string $tokenHash): ?ResetPasswordRequest;

    /** @return list<ResetPasswordRequest> */
    public function findExpired(): array;

    public function remove(ResetPasswordRequest $request): void;
}
