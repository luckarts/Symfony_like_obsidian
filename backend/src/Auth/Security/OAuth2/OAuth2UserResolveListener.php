<?php

declare(strict_types=1);

namespace App\Auth\Security\OAuth2;

use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\User\Infrastructure\Security\SecurityUser;
use App\User\Infrastructure\Security\UserProvider;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsEventListener(event: 'league.oauth2_server.event.user_resolve')]
class OAuth2UserResolveListener
{
    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $securityLogger,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(UserResolveEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp();
        $userAgent = $request?->headers->get('User-Agent');

        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->userProvider->loadUserByIdentifier($event->getUsername());
        } catch (UserNotFoundException) {
            $this->securityLogger->warning('login_failed', [
                'reason' => 'user_not_found',
                'userId' => null,
                'emailAttempted' => $event->getUsername(),
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginFailed('user_not_found', $event->getUsername(), $ip, $userAgent),
            );

            return;
        }

        if (!$this->passwordHasher->isPasswordValid($securityUser, $event->getPassword())) {
            $this->securityLogger->warning('login_failed', [
                'reason' => 'bad_password',
                'userId' => $securityUser->getUser()->getId(),
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginFailed('bad_password', null, $ip, $userAgent, $securityUser->getUser()->getId()),
            );

            return;
        }

        $this->securityLogger->info('login_success', [
            'userId' => $securityUser->getUser()->getId(),
            'ip' => $ip,
            'userAgent' => $userAgent,
        ]);
        $this->securityEventRepository->save(
            SecurityEvent::loginSuccess((string) $securityUser->getUser()->getId(), null, $ip, $userAgent),
        );

        $event->setUser($securityUser);
    }
}
