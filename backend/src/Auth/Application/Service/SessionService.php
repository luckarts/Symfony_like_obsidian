<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\View\SessionView;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

final class SessionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenRevocationService $tokenRevocationService,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
    ) {
    }

    /**
     * @return SessionView[]
     */
    public function listSessions(string $userIdentifier): array
    {
        $tokens = $this->findActiveTokens($userIdentifier);

        return array_map(fn (RefreshToken $token) => new SessionView(
            id: $this->hashIdentifier($token->getIdentifier()),
            expiresAt: $token->getExpiry(),
        ), $tokens);
    }

    /**
     * Revoke a specific session by its hashed identifier.
     */
    public function revokeSession(string $userIdentifier, string $userUuid, string $sessionId): bool
    {
        $tokens = $this->findActiveTokens($userIdentifier);

        foreach ($tokens as $token) {
            if ($this->hashIdentifier($token->getIdentifier()) !== $sessionId) {
                continue;
            }

            $accessToken = $token->getAccessToken();

            if (!$token->isRevoked()) {
                $this->entityManager->persist($token->revoke());
            }

            if ($accessToken !== null && !$accessToken->isRevoked()) {
                $this->entityManager->persist($accessToken->revoke());
            }

            $this->entityManager->flush();

            $securityEvent = SecurityEvent::tokenRevoked(
                userId: $userUuid,
                ip: null,
                userAgent: null,
                reason: 'session_revoke',
            );
            $this->securityEventRepository->save($securityEvent);

            return true;
        }

        return false;
    }

    /**
     * Revoke all sessions for the user.
     */
    public function revokeAll(string $userIdentifier, string $userUuid): void
    {
        $this->tokenRevocationService->revokeAllForUser($userIdentifier);

        $securityEvent = SecurityEvent::tokenRevoked(
            userId: $userUuid,
            ip: null,
            userAgent: null,
        );
        $this->securityEventRepository->save($securityEvent);
    }

    private function hashIdentifier(string $identifier): string
    {
        return \substr(\hash('sha256', $identifier), 0, 16);
    }

    /**
     * @return RefreshToken[]
     */
    private function findActiveTokens(string $userIdentifier): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('rt')
            ->from(RefreshToken::class, 'rt')
            ->innerJoin('rt.accessToken', 'at')
            ->where('at.userIdentifier = :userId')
            ->andWhere('rt.revoked = :revoked')
            ->andWhere('rt.expiry > :now')
            ->setParameter('userId', $userIdentifier)
            ->setParameter('revoked', false)
            ->setParameter('now', new \DateTimeImmutable());

        /** @var RefreshToken[] */
        return $qb->getQuery()->getResult();
    }
}
