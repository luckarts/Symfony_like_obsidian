<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasherFactory
            ->getPasswordHasher(User::class)
            ->hash($plainPassword);
    }
}