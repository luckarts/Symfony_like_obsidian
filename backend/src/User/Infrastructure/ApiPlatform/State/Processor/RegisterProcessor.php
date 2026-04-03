<?php

declare(strict_types=1);

namespace App\User\Infrastructure\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Infrastructure\ApiPlatform\Resource\RegisterUserRequest;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @implements ProcessorInterface<RegisterUserRequest, UserResource>
 */
class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        assert($data instanceof RegisterUserRequest);

        $user = new User();
        $user->setEmail($data->email);
        $user->setPassword($this->passwordHasherFactory->getPasswordHasher(User::class)->hash($data->password));
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);

        $this->userRepository->save($user);

        return UserResource::fromEntity($user);
    }
}
