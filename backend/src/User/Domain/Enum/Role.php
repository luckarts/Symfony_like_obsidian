<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum Role: string
{
    case ROLE_DEMO = 'ROLE_DEMO';
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
}
