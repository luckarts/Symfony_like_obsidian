<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

final class BruteForceLockedException extends \RuntimeException
{
    private const VALID_REASONS = ['rate_limited_ip', 'rate_limited_identifier'];

    public readonly int $retryAfterSeconds;
    public readonly string $reason;

    public function __construct(int $retryAfterSeconds, string $reason)
    {
        if (!in_array($reason, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid reason "%s". Must be one of: %s', $reason, implode(', ', self::VALID_REASONS))
            );
        }

        $this->retryAfterSeconds = $retryAfterSeconds;
        $this->reason = $reason;

        parent::__construct(sprintf('Brute force attempt detected. Retry after %d seconds', $retryAfterSeconds));
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
