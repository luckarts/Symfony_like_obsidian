<?php

declare(strict_types=1);

namespace App\Auth\Security\OAuth2;

use App\User\Infrastructure\Security\SecurityUser;
use App\User\Infrastructure\Security\UserProvider;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsEventListener(event: 'league.oauth2_server.event.user_resolve')]
class OAuth2UserResolveListener
{
    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(UserResolveEvent $event): void
    {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->userProvider->loadUserByIdentifier($event->getUsername());
        } catch (UserNotFoundException) {
            return;
        }

        if (!$this->passwordHasher->isPasswordValid($securityUser, $event->getPassword())) {
            return;
        }

        $event->setUser($securityUser);
    }
}