<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Command\UpdateProfileCommand;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;

class UpdateProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function update(User $user, UpdateProfileCommand $command): User
    {
        $user->setFirstName($command->firstName);
        $user->setLastName($command->lastName);

        $this->userRepository->save($user);

        return $user;
    }
}
