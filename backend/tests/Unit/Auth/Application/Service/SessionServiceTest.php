<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application\Service;

use App\Auth\Application\Service\SessionService;
use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Auth\Application\Service\SessionService
 */
class SessionServiceTest extends TestCase
{
    private SessionService $service;
    private EntityManagerInterface&MockObject $entityManager;
    private TokenRevocationService&MockObject $tokenRevocationService;
    private SecurityEventRepositoryInterface&MockObject $securityEventRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenRevocationService = $this->createMock(TokenRevocationService::class);
        $this->securityEventRepository = $this->createMock(SecurityEventRepositoryInterface::class);

        $this->service = new SessionService(
            $this->entityManager,
            $this->tokenRevocationService,
            $this->securityEventRepository,
        );
    }

    public function testListSessionsReturnsActiveSessions(): void
    {
        $userId = 'user-123';
        $now = new \DateTimeImmutable();
        $future = $now->modify('+1 hour');

        $accessToken = $this->createAccessToken($userId);
        $token1 = $this->createRefreshToken('token-a', $future, $accessToken, false);
        $token2 = $this->createRefreshToken('token-b', $future, $accessToken, false);

        $this->mockFindActiveTokens($userId, [$token1, $token2]);

        $sessions = $this->service->listSessions($userId);

        self::assertCount(2, $sessions);
        self::assertSame(16, \strlen($sessions[0]->id));
        self::assertSame(16, \strlen($sessions[1]->id));
        self::assertSame($future->format(\DateTimeInterface::ATOM), $sessions[0]->expiresAt->format(\DateTimeInterface::ATOM));
    }

    public function testListSessionsReturnsEmptyWhenNoActiveTokens(): void
    {
        $this->mockFindActiveTokens('user-123', []);

        $sessions = $this->service->listSessions('user-123');

        self::assertEmpty($sessions);
    }

    public function testRevokeSessionRevokesMatchingTokenAndAccessToken(): void
    {
        $userId = 'user-123';
        $now = new \DateTimeImmutable();
        $future = $now->modify('+1 hour');

        $accessToken = $this->createAccessToken($userId);
        $token = $this->createRefreshToken('token-abc', $future, $accessToken, false);

        $this->mockFindActiveTokens($userId, [$token]);

        $this->entityManager->expects(self::exactly(2))
            ->method('persist');
        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->securityEventRepository->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(SecurityEvent::class));

        $sessionId = \substr(\hash('sha256', 'token-abc'), 0, 16);
        $result = $this->service->revokeSession($userId, '550e8400-e29b-41d4-a716-446655440000', $sessionId);

        self::assertTrue($result);
    }

    public function testRevokeSessionReturnsFalseWhenNoMatch(): void
    {
        $userId = 'user-123';
        $now = new \DateTimeImmutable();
        $future = $now->modify('+1 hour');

        $accessToken = $this->createAccessToken($userId);
        $token = $this->createRefreshToken('token-abc', $future, $accessToken, false);

        $this->mockFindActiveTokens($userId, [$token]);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');
        $this->securityEventRepository->expects(self::never())->method('save');

        $result = $this->service->revokeSession($userId, '550e8400-e29b-41d4-a716-446655440000', 'nonexistent-hash');

        self::assertFalse($result);
    }

    public function testRevokeAllCallsTokenRevocationService(): void
    {
        $userId = 'user-123';

        $this->tokenRevocationService->expects(self::once())
            ->method('revokeAllForUser')
            ->with($userId);

        $this->securityEventRepository->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(SecurityEvent::class));

        $this->service->revokeAll($userId, '550e8400-e29b-41d4-a716-446655440000');
    }

    private function createAccessToken(string $userId): AccessTokenInterface
    {
        $accessToken = $this->createMock(AccessTokenInterface::class);
        $accessToken->method('getUserIdentifier')->willReturn($userId);
        $accessToken->method('isRevoked')->willReturn(false);
        $accessToken->method('revoke')->willReturnSelf();
        $accessToken->method('getIdentifier')->willReturn('access-token-id');

        return $accessToken;
    }

    private function createRefreshToken(
        string $identifier,
        \DateTimeInterface $expiry,
        AccessTokenInterface $accessToken,
        bool $revoked,
    ): RefreshToken {
        $token = $this->createMock(RefreshToken::class);
        $token->method('getIdentifier')->willReturn($identifier);
        $token->method('getExpiry')->willReturn($expiry);
        $token->method('getAccessToken')->willReturn($accessToken);
        $token->method('isRevoked')->willReturn($revoked);
        $token->method('revoke')->willReturnSelf();

        return $token;
    }

    /**
     * @param RefreshToken[] $tokens
     */
    private function mockFindActiveTokens(string $userId, array $tokens): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $q = $this->createMock(Query::class);

        $this->entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();

        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($q);

        $q->expects(self::once())
            ->method('getResult')
            ->willReturn($tokens);
    }
}
