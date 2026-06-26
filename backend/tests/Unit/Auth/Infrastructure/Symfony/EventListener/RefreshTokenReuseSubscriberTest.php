<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Infrastructure\Symfony\EventListener;

use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Infrastructure\Symfony\EventListener\RefreshTokenReuseSubscriber;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \App\Auth\Infrastructure\Symfony\EventListener\RefreshTokenReuseSubscriber
 */
class RefreshTokenReuseSubscriberTest extends TestCase
{
    private RefreshTokenReuseSubscriber $subscriber;
    private TokenRevocationService&MockObject $tokenRevocationService;
    private LoggerInterface&MockObject $logger;
    private SecurityEventRepositoryInterface&MockObject $securityEventRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private string $encryptionKey = 'test-secret-key';

    protected function setUp(): void
    {
        $this->tokenRevocationService = $this->createMock(TokenRevocationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->securityEventRepository = $this->createMock(SecurityEventRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->subscriber = new RefreshTokenReuseSubscriber(
            $this->tokenRevocationService,
            $this->securityEventRepository,
            $this->userRepository,
            $this->logger,
            $this->encryptionKey,
        );
    }

    public function testSubscribedEvents(): void
    {
        $this->assertSame([
            KernelEvents::RESPONSE => 'onKernelResponse',
        ], $this->subscriber->getSubscribedEvents());
    }

    /** @return array<string, array{Request, Response}> */
    public static function onKernelResponseProvideEvents(): array
    {
        return [
            'non POST request' => [
                Request::create('/oauth2/token', 'GET'),
                new Response(),
            ],
            'wrong path' => [
                Request::create('/oauth2/authorize', 'POST'),
                new Response('', 400, ['Content-Type' => 'application/json']),
            ],
            'non 400 response' => [
                Request::create('/oauth2/token', 'POST'),
                new Response(),
            ],
            'wrong hint' => [
                Request::create('/oauth2/token', 'POST'),
                new Response(json_encode(['hint' => 'Invalid grant']), 400, ['Content-Type' => 'application/json']),
            ],
            'missing refresh token' => [
                Request::create('/oauth2/token', 'POST'),
                new Response(json_encode(['hint' => 'Token has been revoked']), 400, ['Content-Type' => 'application/json']),
            ],
        ];
    }

    #[DataProvider('onKernelResponseProvideEvents')]
    public function testOnKernelResponseDoesNothingWhenConditionsNotMet(
        Request $request,
        Response $response,
    ): void {
        $event = $this->createResponseEvent($request, $response);

        $this->securityEventRepository->expects($this->never())->method('save');
        $this->tokenRevocationService->expects($this->never())->method('revokeAllForUser');

        $this->subscriber->onKernelResponse($event);
    }

    public function testOnKernelResponseLogsAndRevokesWhenRefreshTokenReuseDetected(): void
    {
        $userId = '123e4567-e89b-12d3-a456-426614174000';
        $ip = '192.168.1.1';
        $userAgent = 'Mozilla/5.0';

        $plainToken = json_encode([
            'refresh_token_id' => 'refresh-token-123',
            'user_id' => $userId,
            'access_token_id' => 'access-token-123',
            'client_id' => 'client-1',
            'expire_time' => time() + 3600,
            'scope' => '*',
        ]);
        $encryptedRefreshToken = \Defuse\Crypto\Crypto::encryptWithPassword($plainToken, $this->encryptionKey);

        $request = Request::create('/oauth2/token', 'POST');
        $request->request->set('refresh_token', $encryptedRefreshToken);
        $request->server->set('REMOTE_ADDR', $ip);
        $request->headers->set('User-Agent', $userAgent);

        $response = new Response(json_encode(['hint' => 'Token has been revoked']), 400, ['Content-Type' => 'application/json']);
        $event = $this->createResponseEvent($request, $response);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($userId)
            ->willReturn($user);

        $this->securityEventRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SecurityEvent::class));

        $this->tokenRevocationService->expects($this->once())
            ->method('revokeAllForUser')
            ->with($userId);

        $this->subscriber->onKernelResponse($event);
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
