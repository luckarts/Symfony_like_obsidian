<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\ApiPlatform\State\Processor\ChangeUserRoleProcessor;
use App\User\Infrastructure\ApiPlatform\State\Provider\AdminUsersProvider;
use App\User\Infrastructure\ApiPlatform\Resource\ChangeUserRoleRequest;

#[
    ApiResource(
        shortName: "AdminUser",
        operations: [
            new GetCollection(
                uriTemplate: "/v1/admin/users",
                provider: AdminUsersProvider::class,
                security: "is_granted('ROLE_ADMIN')",
                openapi: new Operation(security: [['BearerAuth' => []]]),
            ),
            new Patch(
                uriTemplate: "/v1/admin/users/{id}/role",
                processor: ChangeUserRoleProcessor::class,
                input: ChangeUserRoleRequest::class,
                inputFormats: ['json' => ['application/json']],
                read: false,
                deserialize: true,
                status: 204,
                security: "is_granted('ROLE_ADMIN')",
                openapi: new Operation(security: [['BearerAuth' => []]]),
            ),
        ],
        paginationItemsPerPage: 20,
        paginationMaximumItemsPerPage: 100,
        paginationClientItemsPerPage: true,
    ),
]
class AdminUserResource
{
    public string $id;

    public string $firstName;

    public string $lastName;

    /** @var list<string> */
    public array $roles;

    public bool $isVerified;

    public string $createdAt;

    public static function fromEntity(User $user): self
    {
        $resource = new self();
        $resource->id = (string) $user->getId();
        $resource->firstName = $user->getFirstName();
        $resource->lastName = $user->getLastName();
        $resource->roles = $user->getRoles();
        $resource->isVerified = $user->isVerified();
        $resource->createdAt = $user->getCreatedAt()->format(\DateTimeInterface::ATOM);
        return $resource;
    }
}
