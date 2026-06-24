<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Infrastructure\ApiPlatform\Resource\SecurityEventResource;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProviderInterface<SecurityEventResource>
 */
class SecurityEventsProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SecurityEventRepositoryInterface $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        $page = (int) ($context['request']->query->get('page', 1));
        $limit = min((int) ($context['request']->query->get('itemsPerPage', 20)), 100);

        $paginator = $this->repository->findByUserId(
            (string) $user->getUser()->getId(),
            $page,
            $limit,
        );

        $resources = [];
        foreach ($paginator as $event) {
            $resources[] = SecurityEventResource::fromEntity($event);
        }

        return new TraversablePaginator(
            new \ArrayIterator($resources),
            (float) $page,
            (float) $limit,
            (float) $paginator->count(),
        );
    }
}
