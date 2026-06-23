<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Domain\Event\HasDomainEventsInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

final class DomainEventDispatchListener
{
    private int $dispatchDepth = 0;

    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->dispatchDepth >= 10) {
            return;
        }

        ++$this->dispatchDepth;

        try {
            $this->dispatchEvents($args);
        } finally {
            --$this->dispatchDepth;
        }
    }

    private function dispatchEvents(PostFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $identityMap = $em->getUnitOfWork()->getIdentityMap();

        foreach ($identityMap as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof HasDomainEventsInterface) {
                    foreach ($entity->pullDomainEvents() as $event) {
                        $this->eventBus->dispatch($event);
                    }
                }
            }
        }
    }
}
