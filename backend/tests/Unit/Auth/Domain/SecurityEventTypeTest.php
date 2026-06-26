<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain;

use App\Auth\Domain\Enum\SecurityEventType;
use PHPUnit\Framework\TestCase;

class SecurityEventTypeTest extends TestCase
{
    public function testTokenRefreshCaseExists(): void
    {
        self::assertSame('token_refresh', SecurityEventType::TOKEN_REFRESH->value);
    }

    public function testTokenReuseDetectedCaseExists(): void
    {
        self::assertSame('token_reuse_detected', SecurityEventType::TOKEN_REUSE_DETECTED->value);
    }

    public function testTokenRevokedCaseExists(): void
    {
        self::assertSame('token_revoked', SecurityEventType::TOKEN_REVOKED->value);
    }
}