<?php

declare(strict_types=1);

namespace App\User\Application\Trait;

trait RecordsDomainEvents
{
    /** @var array<int, object> */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<int, object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
