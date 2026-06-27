<?php

declare(strict_types=1);

namespace App\User\Application\EventHandler;

use App\Email\Application\Service\EmailVerificationService;
use App\User\Domain\Event\UserRegisteredEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
class UserRegisteredEventHandler
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->emailVerificationService->sendVerificationEmail($event->userId);
    }
}
