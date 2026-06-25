<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\EventListener;

use App\Shared\Infrastructure\Symfony\EventListener\RateLimitSubscriber;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Group('unit')]
#[Group('shared')]
class RateLimitSubscriberTest extends TestCase
{
    private RateLimiterFactoryInterface&MockObject $apiDefaultLimiter;
    private TokenStorageInterface&MockObject $tokenStorage;
    private RateLimitSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->apiDefaultLimiter = $this->createMock(RateLimiterFactoryInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->subscriber = new RateLimitSubscriber($this->apiDefaultLimiter, $this->tokenStorage);
    }

    private function makeRequestEvent(Request $request, bool $mainRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $type = $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;
        return new RequestEvent($kernel, $request, $type);
    }

    private function mockAcceptedConsume(string $expectedKey): void
    {
        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(true);

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->with(1)->willReturn($limit);

        $this->apiDefaultLimiter
            ->expects($this->once())
            ->method('create')
            ->with($expectedKey)
            ->willReturn($limiter);
    }

    #[Test]
    public function matching_path_consumes_api_default_limiter(): void
    {
        $request = Request::create('/api/v1/users');
        $event = $this->makeRequestEvent($request);

        $this->mockAcceptedConsume('127.0.0.1'); // assuming no token, so IP only
        $this->tokenStorage->method('getToken')->willReturn(null);

        $this->subscriber->onKernelRequest($event);
        // If we reach here, no exception was thrown, which is what we want for accepted.
    }

    #[Test]
    public function non_matching_path_does_not_consume(): void
    {
        $request = Request::create('/api');
        $event = $this->makeRequestEvent($request);

        $this->apiDefaultLimiter->expects($this->never())->method('create');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function oauth2_token_route_excluded_by_route_name(): void
    {
        $request = Request::create('/api/v1/something');
        $request->attributes->set('_route', 'oauth2_token');
        $event = $this->makeRequestEvent($request);

        $this->apiDefaultLimiter->expects($this->never())->method('create');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function oauth2_authorize_route_excluded(): void
    {
        $request = Request::create('/api/v1/something');
        $request->attributes->set('_route', 'oauth2_authorize');
        $event = $this->makeRequestEvent($request);

        $this->apiDefaultLimiter->expects($this->never())->method('create');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function non_main_request_skipped(): void
    {
        $request = Request::create('/api/v1/users');
        $event = $this->makeRequestEvent($request, false); // sub request

        $this->apiDefaultLimiter->expects($this->never())->method('create');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function authenticated_user_key_includes_user_identifier(): void
    {
        $request = Request::create('/api/v1/users');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $event = $this->makeRequestEvent($request);

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->mockAcceptedConsume('10.0.0.1|user@example.com');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function anonymous_user_uses_ip_only_key(): void
    {
        $request = Request::create('/api/v1/users');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $event = $this->makeRequestEvent($request);

        $this->tokenStorage->method('getToken')->willReturn(null);

        $this->mockAcceptedConsume('10.0.0.1');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function rate_limited_request_throws_too_many_requests(): void
    {
        $request = Request::create('/api/v1/users');
        $event = $this->makeRequestEvent($request);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(false);
        $limit->method('getRetryAfter')->willReturn(new \DateTimeImmutable('+60 seconds'));

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->with(1)->willReturn($limit);

        $this->apiDefaultLimiter
            ->expects($this->once())
            ->method('create')
            ->with('127.0.0.1')
            ->willReturn($limiter);

        $this->expectException(TooManyRequestsHttpException::class);
        $this->expectExceptionMessage('Too many requests. Please wait before retrying.');

        $this->subscriber->onKernelRequest($event);
    }

    #[Test]
    public function rate_limited_request_sets_retry_after_header(): void
    {
        $request = Request::create('/api/v1/users');
        $event = $this->makeRequestEvent($request);

        $limit = $this->createMock(RateLimit::class);
        $limit->method('isAccepted')->willReturn(false);
        $limit->method('getRetryAfter')->willReturn(new \DateTimeImmutable('+120 seconds'));

        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('consume')->with(1)->willReturn($limit);

        $this->apiDefaultLimiter
            ->expects($this->once())
            ->method('create')
            ->with('127.0.0.1')
            ->willReturn($limiter);

        try {
            $this->subscriber->onKernelRequest($event);
            $this->fail('Expected TooManyRequestsHttpException');
        } catch (TooManyRequestsHttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertNotNull($e->getHeaders()['Retry-After'] ?? null);
        }
    }
}