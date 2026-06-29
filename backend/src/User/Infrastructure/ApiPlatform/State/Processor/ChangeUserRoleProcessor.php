<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Enum\Role;
use App\User\Domain\Exception\InvalidRoleException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\ApiPlatform\Resource\ChangeUserRoleRequest;
use App\User\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<ChangeUserRoleRequest, null>
 */
class ChangeUserRoleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->userRepository->findById($uriVariables['id']);
        if (null === $user) {
            throw UserNotFoundException::withId($uriVariables['id']);
        }

        $role = strtoupper(trim($data->role));
        if (!in_array($role, array_map(fn(Role $r) => $r->value, Role::cases()), true)) {
            throw InvalidRoleException::withRole($data->role);
        }

        $user->setRoles([$role]);

        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $currentUserId = (string) $securityUser->getUser()->getId();
        $ip = $context['request']?->getClientIp();
        $userAgent = $context['request']?->headers->get('User-Agent');

        $event = SecurityEvent::roleChanged(
            userId: (string) $user->getId(),
            reason: sprintf('Role changed to %s by admin %s', $role, $currentUserId),
            ip: $ip,
            userAgent: $userAgent,
        );
        $this->securityEventRepository->save($event);
        $this->em->flush();

        return null;
    }
}
