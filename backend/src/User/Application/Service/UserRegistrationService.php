<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Application\Command\RegisterUserCommand;

class UserRegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(RegisterUserCommand $command): User
    {
        if ($this->userRepository->existsByEmail($command->email)) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user = User::register(
            email: $command->email,
            hashedPassword: $this->passwordHasher->hash($command->password),
            firstName: $command->firstName,
            lastName: $command->lastName,
        );

        $this->userRepository->save($user);

        return $user;
    }
}