<?php

declare(strict_types=1);

namespace App\Auth\Domain\Enum;

enum SecurityEventType: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGIN_BLOCKED = 'login_blocked';
    case TOKEN_REFRESH = 'token_refresh';
    case TOKEN_REUSE_DETECTED = 'token_reuse_detected';
    case TOKEN_REVOKED = 'token_revoked';
    case PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    case PASSWORD_RESET_COMPLETED = 'password_reset_completed';
    case ROLE_CHANGED = 'role_changed';
}
