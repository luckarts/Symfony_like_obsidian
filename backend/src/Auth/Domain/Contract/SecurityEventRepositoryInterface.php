<?php

declare(strict_types=1);

namespace App\Auth\Domain\Contract;

use App\Auth\Domain\Entity\SecurityEvent;

interface SecurityEventRepositoryInterface
{
    public function save(SecurityEvent $event, bool $flush = false): void;

    public function findByUserId(string $userId, int $page = 1, int $limit = 20): SecurityEventCollection;
}
