<?php

declare(strict_types=1);

namespace App\User\Application\EventHandler;

use App\Email\Application\Message\SendVerificationEmailMessage;
use App\User\Domain\Event\UserRegisteredEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
class UserRegisteredEventHandler
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->messageBus->dispatch(new SendVerificationEmailMessage(
            userId: $event->userId,
        ));
    }
}
