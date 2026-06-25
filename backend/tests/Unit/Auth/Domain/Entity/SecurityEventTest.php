<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\Entity;

use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Enum\SecurityEventType;
use PHPUnit\Framework\TestCase;

class SecurityEventTest extends TestCase
{
    public function testLoginBlockedFactoryWithAllParameters(): void
    {
        $reason = 'rate_limited_ip';
        $email = 'user@example.com';
        $ip = '192.168.1.1';
        $userAgent = 'Mozilla/5.0';
        $userId = '123e4567-e89b-12d3-a456-426614174000';

        $event = SecurityEvent::loginBlocked($reason, $email, $ip, $userAgent, $userId);

        self::assertSame(SecurityEventType::LOGIN_BLOCKED, $event->getEventType());
        self::assertSame($reason, $event->getReason());
        self::assertSame($email, $event->getEmailAttempted());
        self::assertSame($ip, $event->getIp());
        self::assertSame($userAgent, $event->getUserAgent());
        self::assertSame($userId, $event->getUserId());
    }

    public function testLoginBlockedFactoryWithNullOptionals(): void
    {
        $reason = 'rate_limited_identifier';

        $event = SecurityEvent::loginBlocked($reason, null, null, null, null);

        self::assertSame(SecurityEventType::LOGIN_BLOCKED, $event->getEventType());
        self::assertSame($reason, $event->getReason());
        self::assertNull($event->getEmailAttempted());
        self::assertNull($event->getIp());
        self::assertNull($event->getUserAgent());
        self::assertNull($event->getUserId());
    }

    public function testLoginBlockedFactoryMixedNulls(): void
    {
        $reason = 'rate_limited_ip';
        $email = 'user@example.com';

        $event = SecurityEvent::loginBlocked($reason, $email, null, null);

        self::assertSame(SecurityEventType::LOGIN_BLOCKED, $event->getEventType());
        self::assertSame($reason, $event->getReason());
        self::assertSame($email, $event->getEmailAttempted());
        self::assertNull($event->getIp());
        self::assertNull($event->getUserAgent());
        self::assertNull($event->getUserId());
    }
}
