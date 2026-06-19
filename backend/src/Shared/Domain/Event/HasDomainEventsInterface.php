<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface HasDomainEventsInterface
{
    /** @return object[] */
    public function pullDomainEvents(): array;
}
