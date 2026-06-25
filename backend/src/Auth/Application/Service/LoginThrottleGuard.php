<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Exception\BruteForceLockedException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class LoginThrottleGuard
{
    public function __construct(
        #[Autowire(service: 'limiter.login_ip')]
        private readonly RateLimiterFactory $ipLimiterFactory,
        #[Autowire(service: 'limiter.login_identifier')]
        private readonly RateLimiterFactory $identifierLimiterFactory,
    ) {
    }

    public function assertNotLocked(string $identifier, ?string $ip): void
    {
        $normalizedId = $this->normalizeIdentifier($identifier);

        if ($ip !== null) {
            $ipLimiter = $this->ipLimiterFactory->create($ip);
            $ipResult = $ipLimiter->consume(0);

            if ($ipResult->getRemainingTokens() <= 0) {
                throw new BruteForceLockedException(
                    max(1, (int) ($ipResult->getRetryAfter()->format('U.u') - microtime(true))),
                    'rate_limited_ip',
                );
            }
        }

        $identifierLimiter = $this->identifierLimiterFactory->create($normalizedId);
        $idResult = $identifierLimiter->consume(0);

        if ($idResult->getRemainingTokens() <= 0) {
            throw new BruteForceLockedException(
                max(1, (int) ($idResult->getRetryAfter()->format('U.u') - microtime(true))),
                'rate_limited_identifier',
            );
        }
    }

    public function recordFailure(string $identifier, ?string $ip): void
    {
        $normalizedId = $this->normalizeIdentifier($identifier);

        if ($ip !== null) {
            $ipLimiter = $this->ipLimiterFactory->create($ip);
            $ipLimiter->consume(1);
        }

        $identifierLimiter = $this->identifierLimiterFactory->create($normalizedId);
        $identifierLimiter->consume(1);
    }

    public function recordSuccess(string $identifier): void
    {
        $normalizedId = $this->normalizeIdentifier($identifier);

        $identifierLimiter = $this->identifierLimiterFactory->create($normalizedId);
        $identifierLimiter->reset();
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }
}
