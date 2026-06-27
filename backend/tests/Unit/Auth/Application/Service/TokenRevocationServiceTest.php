<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application\Service;

use App\Auth\Application\Service\TokenRevocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Auth\Application\Service\TokenRevocationService
 */
class TokenRevocationServiceTest extends TestCase
{
    private TokenRevocationService $service;
    private EntityManagerInterface&MockObject $entityManager;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new TokenRevocationService(
            $this->entityManager,
            $this->logger
        );
    }

    public function testRevokeAllForUserRevokesNonRevokedTokens(): void
    {
        $userId = 'user-123';

        // Create token objects that are not revoked
        $token1 = new class {
            private bool $revoked = false;
            public function isRevoked(): bool {
                return $this->revoked;
            }
            public function revoke(): self {
                $this->revoked = true;
                return $this;
            }
        };
        $token2 = new class {
            private bool $revoked = false;
            public function isRevoked(): bool {
                return $this->revoked;
            }
            public function revoke(): self {
                $this->revoked = true;
                return $this;
            }
        };
        // Create a token that is already revoked
        $token3 = new class {
            private bool $revoked = true;
            public function isRevoked(): bool {
                return $this->revoked;
            }
            public function revoke(): self {
                return $this;
            }
        };

        $tokens = [$token1, $token2, $token3];

        // Mock the query builder and query
        $qb = $this->createMock(QueryBuilder::class);
        $q = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($q);
        $q->expects($this->once())
            ->method('getResult')
            ->willReturn($tokens);

        // Expect persist for the two non-revoked tokens
        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->identicalTo($token1),
                $this->identicalTo($token2)
            ));

        // Expect flush once
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->never())
            ->method('error');

        $this->service->revokeAllForUser($userId);
    }

    public function testRevokeAllForUserLogsErrorWhenExceptionOccurs(): void
    {
        $userId = 'user-123';

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to revoke refresh tokens for user'));

        $this->service->revokeAllForUser($userId);
    }
}