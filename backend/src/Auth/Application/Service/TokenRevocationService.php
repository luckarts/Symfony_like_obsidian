<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use Psr\Log\LoggerInterface;

/**
 * Service to revoke all refresh tokens for a given user.
 */
class TokenRevocationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Revoke all refresh tokens for the given user.
     *
     * @param string $userId The user identifier
     */
    public function revokeAllForUser(string $userId): void
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('rt')
                ->from(RefreshToken::class, 'rt')
                ->innerJoin('rt.accessToken', 'at')
                ->where('at.userIdentifier = :userId')
                ->setParameter('userId', $userId);

            $refreshTokens = $qb->getQuery()->getResult();

            foreach ($refreshTokens as $refreshToken) {
                if (!$refreshToken->isRevoked()) {
                    $revokedToken = $refreshToken->revoke();
                    $this->entityManager->persist($revokedToken);
                }
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to revoke refresh tokens for user '.$userId.': '.$e->getMessage());
        }
    }
}