<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

final class InvalidRefreshTokenException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid refresh_token');
    }
}
