<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

final class RefreshTokenOwnershipException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('refresh_token does not belong to current user');
    }
}
