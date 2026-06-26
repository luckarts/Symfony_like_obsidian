<?php

declare(strict_types=1);

namespace App\User\Application\Message;

class SendPasswordResetEmailMessage
{
    public function __construct(
        public readonly string $userId,
        public readonly string $resetToken,
    ) {
    }
}
