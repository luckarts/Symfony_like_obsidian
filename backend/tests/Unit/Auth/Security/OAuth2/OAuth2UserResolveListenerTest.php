<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Security\OAuth2;

use App\Auth\Application\Service\LoginThrottleGuard;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Domain\Exception\BruteForceLockedException;
use App\Auth\Security\OAuth2\OAuth2UserResolveListener;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SecurityUser;
use App\User\Infrastructure\Security\UserProvider;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Group('unit')]
#[Group('auth')]
class OAuth2UserResolveListenerTest extends TestCase
{
    private UserProvider&MockObject $userProvider;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private LoggerInterface&MockObject $securityLogger;
    private SecurityEventRepositoryInterface&MockObject $securityEventRepository;
    private LoginThrottleGuard&MockObject $loginThrottleGuard;
    private OAuth2UserResolveListener $listener;

    protected function setUp(): void
    {
        $this->userProvider = $this->createMock(UserProvider::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->securityLogger = $this->createMock(LoggerInterface::class);
        $this->securityEventRepository = $this->createMock(SecurityEventRepositoryInterface::class);
        $this->loginThrottleGuard = $this->createMock(LoginThrottleGuard::class);

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/oauth2/token', 'POST', server: [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'PHPUnit-Agent',
        ]));

        $this->listener = new OAuth2UserResolveListener(
            $this->userProvider,
            $this->passwordHasher,
            $this->securityLogger,
            $this->securityEventRepository,
            $requestStack,
            $this->loginThrottleGuard,
        );
    }

    #[Test]
    public function unknown_user_logs_and_persists_user_not_found_without_leaking_to_response(): void
    {
        $username = 'ghost@example.com';

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('assertNotLocked')
            ->with($username, '203.0.113.7');

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new UserNotFoundException());

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('recordFailure')
            ->with($username, '203.0.113.7');

        $this->securityLogger
            ->expects($this->once())
            ->method('warning')
            ->with('login_failed', $this->callback(fn (array $context) => 'user_not_found' === $context['reason'] && null === $context['userId'] && $username === $context['emailAttempted']));

        $this->securityEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityEvent $event) use ($username) {
                return SecurityEventType::LOGIN_FAILED === $event->getEventType()
                    && 'user_not_found' === $event->getReason()
                    && null === $event->getUserId()
                    && $username === $event->getEmailAttempted();
            }));

        $event = $this->makeEvent('ghost@example.com', 'irrelevant');
        $this->listener->__invoke($event);

        $this->assertNull($event->getUser());
    }

    #[Test]
    public function wrong_password_logs_and_persists_bad_password_with_user_reference(): void
    {
        $user = User::register('known@example.com', 'hashed', 'Jane', 'Doe');
        $securityUser = new SecurityUser($user);
        $username = 'known@example.com';

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('assertNotLocked')
            ->with($username, '203.0.113.7');

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($securityUser);
        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(false);

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('recordFailure')
            ->with($username, '203.0.113.7');

        $this->securityLogger
            ->expects($this->once())
            ->method('warning')
            ->with('login_failed', $this->callback(fn (array $context) => 'bad_password' === $context['reason'] && $user->getId() === $context['userId'] && $username === $context['emailAttempted']));

        $this->securityEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityEvent $event) use ($user, $username) {
                return SecurityEventType::LOGIN_FAILED === $event->getEventType()
                    && 'bad_password' === $event->getReason()
                    && $user->getId() === $event->getUserId()
                    && $username === $event->getEmailAttempted();
            }));

        $event = $this->makeEvent('known@example.com', 'wrong-password');
        $this->listener->__invoke($event);

        $this->assertNull($event->getUser());
    }

    #[Test]
    public function valid_credentials_log_and_persist_login_success_and_set_user(): void
    {
        $user = User::register('valid@example.com', 'hashed', 'Jane', 'Doe');
        $securityUser = new SecurityUser($user);

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('assertNotLocked')
            ->with('valid@example.com', '203.0.113.7');

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($securityUser);
        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(true);

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('recordSuccess')
            ->with('valid@example.com');

        $this->securityLogger
            ->expects($this->once())
            ->method('info')
            ->with('login_success', $this->callback(fn (array $context) => $user->getId() === $context['userId'] && 'valid@example.com' === $context['emailAttempted']));

        $this->securityEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityEvent $event) use ($user) {
                return SecurityEventType::LOGIN_SUCCESS === $event->getEventType()
                    && $user->getId() === $event->getUserId()
                    && 'valid@example.com' === $event->getEmailAttempted();
            }));

        $event = $this->makeEvent('valid@example.com', 'correct-password');
        $this->listener->__invoke($event);

        $this->assertSame($securityUser, $event->getUser());
    }

    #[Test]
    public function locked_axis_blocks_before_any_user_lookup_and_throws_generic_429(): void
    {
        $username = 'victim@example.com';

        $this->loginThrottleGuard
            ->expects($this->once())
            ->method('assertNotLocked')
            ->with($username, '203.0.113.7')
            ->willThrowException(new BruteForceLockedException(60, 'rate_limited_identifier'));

        $this->userProvider
            ->expects($this->never())
            ->method('loadUserByIdentifier');

        $this->loginThrottleGuard
            ->expects($this->never())
            ->method('recordFailure');

        $this->securityLogger
            ->expects($this->once())
            ->method('warning')
            ->with('login_blocked', $this->callback(fn (array $context) => 'rate_limited_identifier' === $context['reason'] && null === $context['userId'] && $username === $context['emailAttempted']));

        $this->securityEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (SecurityEvent $event) use ($username) {
                return SecurityEventType::LOGIN_BLOCKED === $event->getEventType()
                    && 'rate_limited_identifier' === $event->getReason()
                    && $username === $event->getEmailAttempted();
            }));

        $event = $this->makeEvent($username, 'irrelevant');

        try {
            $this->listener->__invoke($event);
            $this->fail('Expected OAuthServerException to be thrown.');
        } catch (OAuthServerException $exception) {
            $this->assertSame(429, $exception->getHttpStatusCode());
            $this->assertSame('slow_down', $exception->getErrorType());
            $this->assertStringNotContainsString('identifier', (string) $exception->getHint());
            $this->assertStringNotContainsString('ip', (string) $exception->getHint());
        }
    }

    private function makeEvent(string $username, string $password): UserResolveEvent
    {
        return new UserResolveEvent(
            $username,
            $password,
            new Grant('password'),
            new Client('Test Client', 'test_client', 'test_secret'),
        );
    }
}
