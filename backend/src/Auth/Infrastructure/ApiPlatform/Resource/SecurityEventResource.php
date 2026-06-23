<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Infrastructure\ApiPlatform\State\Provider\SecurityEventsProvider;

#[
    ApiResource(
        shortName: "SecurityEvent",
        operations: [
            new GetCollection(
                uriTemplate: "/v1/security/events",
                provider: SecurityEventsProvider::class,
            ),
        ],
        routePrefix: "/api",
        paginationItemsPerPage: 20,
        paginationMaximumItemsPerPage: 100,
        paginationClientItemsPerPage: true,
    ),
]
class SecurityEventResource
{
    public SecurityEventType $eventType;

    public ?string $ip;

    public ?string $userAgent;

    public string $createdAt;

    public static function fromEntity(SecurityEvent $event): self
    {
        $resource = new self();
        $resource->eventType = $event->getEventType();
        $resource->ip = $event->getIp();
        $resource->userAgent = $event->getUserAgent();
        $resource->createdAt = $event->getCreatedAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
