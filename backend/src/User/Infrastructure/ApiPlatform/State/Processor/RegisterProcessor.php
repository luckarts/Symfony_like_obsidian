<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Service\UserRegistrationService;
use App\User\Infrastructure\ApiPlatform\Resource\RegisterUserRequest;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;

/**
 * @implements ProcessorInterface<RegisterUserRequest, UserResource>
 */
class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRegistrationService $registrationService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        assert($data instanceof RegisterUserRequest);

        $user = $this->registrationService->register(new RegisterUserCommand(
            email: $data->email,
            password: $data->password,
            firstName: $data->firstName,
            lastName: $data->lastName,
        ));

        return UserResource::fromEntity($user);
    }
}

