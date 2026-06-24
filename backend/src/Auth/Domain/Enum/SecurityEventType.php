<?php

declare(strict_types=1);

namespace App\Auth\Domain\Enum;

enum SecurityEventType: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
}
