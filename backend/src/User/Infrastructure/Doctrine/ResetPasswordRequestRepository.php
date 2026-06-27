<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\User\Domain\Contract\PasswordResetRequestRepositoryInterface;
use App\User\Domain\Entity\ResetPasswordRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository implements PasswordResetRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function save(ResetPasswordRequest $request): void
    {
        $this->getEntityManager()->persist($request);
    }

    public function findByTokenHash(string $tokenHash): ?ResetPasswordRequest
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function findExpired(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function remove(ResetPasswordRequest $request): void
    {
        $this->getEntityManager()->remove($request);
    }
}
