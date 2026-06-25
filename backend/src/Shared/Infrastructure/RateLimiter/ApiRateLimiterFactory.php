<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RateLimiter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class ApiRateLimiterFactory implements RateLimiterFactoryInterface
{
    public function __construct(#[Autowire(service: 'limiter.api_default')] private RateLimiterFactory $inner)
    {
    }

    public function create(string $key): LimiterInterface
    {
        return $this->inner->create($key);
    }
}
