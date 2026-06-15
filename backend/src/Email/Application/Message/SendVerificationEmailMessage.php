<?php

declare(strict_types=1);

namespace App\Email\Application\Message;

class SendVerificationEmailMessage
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
