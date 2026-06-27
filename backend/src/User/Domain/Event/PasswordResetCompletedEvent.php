<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

final class PasswordResetCompletedEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly \DateTimeImmutable $completedAt,
    ) {
    }
}
