<?php

declare(strict_types=1);

namespace App\Auth\Security\OAuth2;

use App\Auth\Application\Service\LoginThrottleGuard;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Exception\BruteForceLockedException;
use App\User\Infrastructure\Security\SecurityUser;
use App\User\Infrastructure\Security\UserProvider;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\OAuth2\Server\Exception\OAuthServerException;
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
        private readonly LoginThrottleGuard $loginThrottleGuard,
        #[Autowire(param: 'app.require_email_verification')]
        private readonly bool $requireEmailVerification = false,
    ) {
    }

    public function __invoke(UserResolveEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp();
        $userAgent = $request?->headers->get('User-Agent');
        $identifier = $event->getUsername();

        try {
            $this->loginThrottleGuard->assertNotLocked($identifier, $ip);
        } catch (BruteForceLockedException $bruteForceLockedException) {
            $this->securityLogger->warning('login_blocked', [
                'reason' => $bruteForceLockedException->getReason(),
                'userId' => null,
                'emailAttempted' => $identifier,
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginBlocked($bruteForceLockedException->getReason(), $identifier, $ip, $userAgent),
            );

            throw new OAuthServerException(
                'Too many failed login attempts.',
                20,
                'slow_down',
                429,
                'Please wait before retrying.',
            );
        }

        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->userProvider->loadUserByIdentifier($identifier);
        } catch (UserNotFoundException) {
            $this->loginThrottleGuard->recordFailure($identifier, $ip);
            $this->securityLogger->warning('login_failed', [
                'reason' => 'user_not_found',
                'userId' => null,
                'emailAttempted' => $identifier,
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginFailed('user_not_found', $identifier, $ip, $userAgent),
            );

            return;
        }

        if (!$this->passwordHasher->isPasswordValid($securityUser, $event->getPassword())) {
            $this->loginThrottleGuard->recordFailure($identifier, $ip);
            $this->securityLogger->warning('login_failed', [
                'reason' => 'bad_password',
                'userId' => $securityUser->getUser()->getId(),
                'emailAttempted' => $identifier,
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginFailed('bad_password', $identifier, $ip, $userAgent, $securityUser->getUser()->getId()),
            );

            return;
        }

        if ($this->requireEmailVerification && !$securityUser->getUser()->isVerified()) {
            $this->securityLogger->warning('login_failed', [
                'reason' => 'email_not_verified',
                'userId' => $securityUser->getUser()->getId(),
                'emailAttempted' => $identifier,
                'ip' => $ip,
                'userAgent' => $userAgent,
            ]);
            $this->securityEventRepository->save(
                SecurityEvent::loginFailed('email_not_verified', $identifier, $ip, $userAgent, $securityUser->getUser()->getId()),
            );

            return;
        }

        $this->loginThrottleGuard->recordSuccess($identifier);

        $this->securityLogger->info('login_success', [
            'userId' => $securityUser->getUser()->getId(),
            'emailAttempted' => $identifier,
            'ip' => $ip,
            'userAgent' => $userAgent,
        ]);
        $this->securityEventRepository->save(
            SecurityEvent::loginSuccess((string) $securityUser->getUser()->getId(), $identifier, $ip, $userAgent),
        );

        $event->setUser($securityUser);
    }
}
