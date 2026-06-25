<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

use Symfony\Component\RateLimiter\RateLimiter;

interface RateLimiterFactoryInterface
{
    public function create(string $key): object;
}
