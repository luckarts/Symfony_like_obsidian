<?php

declare(strict_types=1);

namespace App\Auth\Domain\View;

final class SessionView
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeInterface $expiresAt,
    ) {
    }
}
