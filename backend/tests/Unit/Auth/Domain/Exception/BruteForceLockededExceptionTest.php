<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\Exception;

use App\Auth\Domain\Exception\BruteForceLockedException;
use PHPUnit\Framework\TestCase;

class BruteForceLockededExceptionTest extends TestCase
{
    public function testConstructorWithValidReasonRateLimitedIp(): void
    {
        $retryAfter = 120;
        $reason = 'rate_limited_ip';

        $exception = new BruteForceLockedException($retryAfter, $reason);

        self::assertSame($retryAfter, $exception->getRetryAfterSeconds());
        self::assertSame($reason, $exception->getReason());
        self::assertSame($retryAfter, $exception->retryAfterSeconds);
        self::assertSame($reason, $exception->reason);
    }

    public function testConstructorWithValidReasonRateLimitedIdentifier(): void
    {
        $retryAfter = 300;
        $reason = 'rate_limited_identifier';

        $exception = new BruteForceLockedException($retryAfter, $reason);

        self::assertSame($retryAfter, $exception->getRetryAfterSeconds());
        self::assertSame($reason, $exception->getReason());
    }

    public function testConstructorWithInvalidReasonThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid reason/');

        new BruteForceLockedException(120, 'invalid_reason');
    }

    public function testExceptionMessage(): void
    {
        $retryAfter = 60;
        $exception = new BruteForceLockedException($retryAfter, 'rate_limited_ip');

        self::assertStringContainsString('Brute force attempt detected', $exception->getMessage());
        self::assertStringContainsString((string) $retryAfter, $exception->getMessage());
    }
}
