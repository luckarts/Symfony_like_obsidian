<?php

declare(strict_types=1);

namespace App\Auth\Domain\Contract;

use App\Auth\Domain\Entity\SecurityEvent;
use Doctrine\ORM\Tools\Pagination\Paginator;

interface SecurityEventRepositoryInterface
{
    public function save(SecurityEvent $event, bool $flush = false): void;

    /** @return Paginator<SecurityEvent> */
    public function findByUserId(string $userId, int $page = 1, int $limit = 20): Paginator;
}
