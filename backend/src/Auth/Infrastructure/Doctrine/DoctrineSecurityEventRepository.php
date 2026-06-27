<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Doctrine;

use App\Auth\Domain\Contract\SecurityEventCollection;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityEvent>
 */
class DoctrineSecurityEventRepository extends ServiceEntityRepository implements SecurityEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityEvent::class);
    }

    public function save(SecurityEvent $event, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($event);
        if ($flush) {
            $entityManager->flush();
        }
    }

    public function findByUserId(string $userId, int $page = 1, int $limit = 20): SecurityEventCollection
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query);

        return new SecurityEventCollection(
            items: iterator_to_array($paginator),
            totalItems: (int) $paginator->count(),
            currentPage: $page,
            itemsPerPage: $limit,
        );
    }

    public function findRecentRevokedByUserAndReason(string $userId, string $reason): ?SecurityEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->andWhere('e.reason = :reason')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('userId', $userId)
            ->setParameter('reason', $reason)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
