<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Doctrine;

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

    /** @return Paginator<SecurityEvent> */
    public function findByUserId(string $userId, int $page = 1, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
    }
}
