<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RateLimiter;

use Symfony\Component\RateLimiter\LimiterInterface;

interface RateLimiterFactoryInterface
{
    public function create(string $key): LimiterInterface;
}
