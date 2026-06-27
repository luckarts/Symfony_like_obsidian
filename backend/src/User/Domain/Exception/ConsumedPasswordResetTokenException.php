<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class ConsumedPasswordResetTokenException extends \DomainException
{
    public static function create(): self
    {
        return new self('Password reset token has already been consumed.');
    }
}
