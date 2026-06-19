<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public static function withId(string $userId): self
    {
        return new self(sprintf('User with id "%s" not found.', $userId));
    }
}
