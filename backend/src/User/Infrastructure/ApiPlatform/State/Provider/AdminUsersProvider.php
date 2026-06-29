<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Infrastructure\ApiPlatform\Resource\AdminUserResource;

/**
 * @implements ProviderInterface<AdminUserResource>
 */
class AdminUsersProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $page = (int) ($context['request']->query->get('page', 1));
        $limit = min((int) ($context['request']->query->get('itemsPerPage', 20)), 100);

        $collection = $this->repository->findAllPaginated($page, $limit);

        $resources = [];
        foreach ($collection->getItems() as $user) {
            $resources[] = AdminUserResource::fromEntity($user);
        }

        return new TraversablePaginator(
            new \ArrayIterator($resources),
            (float) $collection->getCurrentPage(),
            (float) $collection->getItemsPerPage(),
            (float) $collection->getTotalItems(),
        );
    }
}
