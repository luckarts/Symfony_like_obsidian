<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Email\Application\Message\SendVerificationEmailMessage;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Service\UserRegistrationService;
use App\User\Infrastructure\ApiPlatform\Resource\RegisterUserRequest;
use App\User\Infrastructure\ApiPlatform\Resource\UserResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @implements ProcessorInterface<RegisterUserRequest, UserResource>
 */
class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRegistrationService $registrationService,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        /** @phpstan-ignore-next-line function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($data instanceof RegisterUserRequest);

        $user = $this->registrationService->register(new RegisterUserCommand(
            email: $data->email,
            password: $data->password,
            firstName: $data->firstName,
            lastName: $data->lastName,
        ));

        $this->entityManager->flush();

        $this->messageBus->dispatch(new SendVerificationEmailMessage(
            userId: (string) $user->getId(),
        ));

        return UserResource::fromEntity($user);
    }
}

