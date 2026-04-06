<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\User\Infrastructure\ApiPlatform\State\Provider\ProfileProvider;

#[ApiResource(
    shortName: 'UserProfile',
    operations: [
        new Get(
            uriTemplate: '/users/{id}',
            provider: ProfileProvider::class,
        ),
    ],
    routePrefix: '/api',
)]
class UserProfile
{
    public string $id = '';
    public string $email = '';
    public string $firstName = '';
    public string $lastName = '';

    /** @var list<string> */
    public array $roles = [];

    public string $createdAt = '';
}