<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\ApiPlatform\State\Provider\ProfileProvider;
use App\User\Infrastructure\ApiPlatform\State\Processor\UpdateProfileProcessor;

#[ApiResource(
    shortName: 'UserProfile',
    operations: [
        new Get(
            uriTemplate: '/users/{id}',
            provider: ProfileProvider::class,
        ),
        new Put(
            uriTemplate: '/users/{id}/profile',
            provider: ProfileProvider::class,
            processor: UpdateProfileProcessor::class,
        ),
    ],
    routePrefix: '/api',
)]
class UserProfile
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id = '',
        public string $email = '',
        public string $firstName = '',
        public string $lastName = '',
        public array $roles = [],
        public string $createdAt = '',
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            roles: $user->getRoles(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
