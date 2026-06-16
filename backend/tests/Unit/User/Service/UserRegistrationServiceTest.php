<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Service;

use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Service\UserRegistrationService;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('user')]
class UserRegistrationServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;
    private PasswordHasherInterface&MockObject $hasher;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->hasher = $this->createMock(PasswordHasherInterface::class);
        $this->service = new UserRegistrationService($this->repository, $this->hasher);
    }

    #[Test]
    public function register_saves_user_with_hashed_password(): void
    {
        $this->repository
            ->method('existsByEmail')
            ->willReturn(false);

        $this->hasher
            ->method('hash')
            ->willReturn('hashed_password');

        $this->repository
            ->expects($this->once())
            ->method('save');

        $user = $this->service->register($this->makeCommand());

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
    }

    private function makeCommand(): RegisterUserCommand
    {
        return new RegisterUserCommand(
            email: 'test@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
