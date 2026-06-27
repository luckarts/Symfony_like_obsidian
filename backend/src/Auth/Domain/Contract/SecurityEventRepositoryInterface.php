<?php

declare(strict_types=1);

namespace App\Auth\Domain\Contract;

use App\Auth\Domain\Entity\SecurityEvent;

interface SecurityEventRepositoryInterface
{
    public function save(SecurityEvent $event, bool $flush = false): void;

    public function findByUserId(string $userId, int $page = 1, int $limit = 20): SecurityEventCollection;

    /**
     * Find the most recent TOKEN_REVOKED event for a user with a given reason.
     */
    public function findRecentRevokedByUserAndReason(string $userId, string $reason): ?SecurityEvent;
}
