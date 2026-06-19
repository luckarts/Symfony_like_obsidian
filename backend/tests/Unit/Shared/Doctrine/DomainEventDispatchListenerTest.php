<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Doctrine;

use App\Shared\Domain\Event\HasDomainEventsInterface;
use App\Shared\Infrastructure\Doctrine\DomainEventDispatchListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[Group('unit')]
#[Group('shared')]
class DomainEventDispatchListenerTest extends TestCase
{
    private MessageBusInterface&MockObject $eventBus;
    private DomainEventDispatchListener $listener;

    protected function setUp(): void
    {
        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->listener = new DomainEventDispatchListener($this->eventBus);
    }

    #[Test]
    public function postFlush_dispatches_events_from_identity_map(): void
    {
        $event1 = new \stdClass();
        $event2 = new \stdClass();

        $entity = $this->createMock(HasDomainEventsInterface::class);
        $entity->expects($this->once())
            ->method('pullDomainEvents')
            ->willReturn([$event1, $event2]);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getIdentityMap')
            ->willReturn([spl_object_hash($entity) => [$entity]]);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $args = $this->createMock(PostFlushEventArgs::class);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($em);

        $this->eventBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->listener->postFlush($args);
    }

    #[Test]
    public function postFlush_ignores_entities_without_domain_events(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getIdentityMap')
            ->willReturn([spl_object_hash($entity) => [$entity]]);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $args = $this->createMock(PostFlushEventArgs::class);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($em);

        $this->eventBus->expects($this->never())->method('dispatch');

        $this->listener->postFlush($args);
    }
}
