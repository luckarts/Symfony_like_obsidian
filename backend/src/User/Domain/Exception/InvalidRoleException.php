<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidRoleException extends \DomainException
{
    public static function withRole(string $role): self
    {
        return new self(sprintf('Invalid role "%s".', $role));
    }
}
