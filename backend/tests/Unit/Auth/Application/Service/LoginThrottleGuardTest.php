<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application\Service;

use App\Auth\Application\Service\LoginThrottleGuard;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class LoginThrottleGuardTest extends TestCase
{
    private LoginThrottleGuard $guard;

    protected function setUp(): void
    {
        $ipFactory = new RateLimiterFactory(
            [
                'id' => 'test_ip',
                'policy' => 'sliding_window',
                'limit' => 20,
                'interval' => '60 seconds',
            ],
            new InMemoryStorage(),
        );

        $idFactory = new RateLimiterFactory(
            [
                'id' => 'test_id',
                'policy' => 'sliding_window',
                'limit' => 5,
                'interval' => '60 seconds',
            ],
            new InMemoryStorage(),
        );

        $this->guard = $this->createGuardWithFactories($ipFactory, $idFactory);
    }

    public function testNormalizationConsistency(): void
    {
        $reflection = new \ReflectionClass(LoginThrottleGuard::class);
        $method = $reflection->getMethod('normalizeIdentifier');
        $method->setAccessible(true);

        $id1 = 'User@Example.com ';
        $id2 = 'user@example.com';

        self::assertSame($method->invoke($this->guard, $id1), $method->invoke($this->guard, $id2));
    }

    #[DoesNotPerformAssertions]
    public function testAssertNotLockedWithValidCredentials(): void
    {
        $this->guard->assertNotLocked('user@example.com', '192.168.1.1');
    }

    #[DoesNotPerformAssertions]
    public function testRecordFailureDoesNotThrow(): void
    {
        $this->guard->recordFailure('user@example.com', '192.168.1.1');
    }

    #[DoesNotPerformAssertions]
    public function testRecordFailureWithoutIpDoesNotThrow(): void
    {
        $this->guard->recordFailure('user@example.com', null);
    }

    #[DoesNotPerformAssertions]
    public function testRecordSuccessDoesNotThrow(): void
    {
        $this->guard->recordSuccess('user@example.com');
    }

    private function createGuardWithFactories(RateLimiterFactory $ipFactory, RateLimiterFactory $idFactory): LoginThrottleGuard
    {
        $reflection = new \ReflectionClass(LoginThrottleGuard::class);
        $guard = $reflection->newInstanceWithoutConstructor();

        $ipProp = $reflection->getProperty('ipLimiterFactory');
        $ipProp->setAccessible(true);
        $ipProp->setValue($guard, $ipFactory);

        $idProp = $reflection->getProperty('identifierLimiterFactory');
        $idProp->setAccessible(true);
        $idProp->setValue($guard, $idFactory);

        return $guard;
    }
}
