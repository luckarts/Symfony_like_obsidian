<?php

declare(strict_types=1);

namespace App\Auth\Domain\Contract;

use App\Auth\Domain\Entity\SecurityEvent;

final class SecurityEventCollection
{
    /** @param SecurityEvent[] $items */
    public function __construct(
        private readonly array $items,
        private readonly int $totalItems,
        private readonly int $currentPage,
        private readonly int $itemsPerPage,
    ) {
    }

    /** @return SecurityEvent[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }
}
