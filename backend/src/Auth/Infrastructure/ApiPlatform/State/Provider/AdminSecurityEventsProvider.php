<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Infrastructure\ApiPlatform\Resource\SecurityEventResource;

/**
 * @implements ProviderInterface<SecurityEventResource>
 */
class AdminSecurityEventsProvider implements ProviderInterface
{
    public function __construct(
        private readonly SecurityEventRepositoryInterface $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $page = (int) ($context['request']->query->get('page', 1));
        $limit = min((int) ($context['request']->query->get('itemsPerPage', 20)), 100);

        $collection = $this->repository->findAllPaginated($page, $limit);

        $resources = [];
        foreach ($collection->getItems() as $event) {
            $resources[] = SecurityEventResource::fromEntity($event);
        }

        return new TraversablePaginator(
            new \ArrayIterator($resources),
            (float) $collection->getCurrentPage(),
            (float) $collection->getItemsPerPage(),
            (float) $collection->getTotalItems(),
        );
    }
}
