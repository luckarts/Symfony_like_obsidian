<?php

declare(strict_types=1);

namespace App\Auth\Domain\Enum;

enum SecurityEventType: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case TOKEN_REFRESH = 'token_refresh';
    case TOKEN_REUSE_DETECTED = 'token_reuse_detected';
    case LOGOUT = 'logout';
    case ACCOUNT_LOCKED = 'account_locked';
}
