<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

final class UserRegisteredEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly \DateTimeImmutable $registeredAt,
    ) {
    }
}
