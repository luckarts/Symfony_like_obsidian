<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Contract\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(
            $this->createStubUser($plainPassword),
            $plainPassword,
        );
    }

    private function createStubUser(string $password): PasswordAuthenticatedUserInterface
    {
        return new class($password) implements PasswordAuthenticatedUserInterface {
            public function __construct(private readonly string $password)
            {
            }

            public function getPassword(): string
            {
                return $this->password;
            }
        };
    }
}