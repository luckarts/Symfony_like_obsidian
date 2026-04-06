<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Infrastructure\ApiPlatform\Resource\UserProfile;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<UserProfile>
 */
class ProfileProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserProfile
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $user = $securityUser->getUser();

        $profile = new UserProfile();
        $profile->id = (string) $user->getId();
        $profile->email = $user->getEmail();
        $profile->firstName = $user->getFirstName();
        $profile->lastName = $user->getLastName();
        $profile->roles = $user->getRoles();
        $profile->createdAt = $user->getCreatedAt()->format(\DateTimeInterface::ATOM);

        return $profile;
    }
}
