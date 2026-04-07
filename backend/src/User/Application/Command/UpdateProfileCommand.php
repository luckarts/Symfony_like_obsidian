<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final readonly class UpdateProfileCommand
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }
}
