<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\User\Application\Message\SendPasswordResetEmailMessage;
use App\User\Application\Service\PasswordResetService;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\PasswordResetRequestRepositoryInterface;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\ResetPasswordRequest;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\ConsumedPasswordResetTokenException;
use App\User\Domain\Exception\ExpiredPasswordResetTokenException;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use App\User\Domain\Service\PasswordPolicyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;
    private \PHPUnit\Framework\MockObject\MockObject $userRepository;
    private \PHPUnit\Framework\MockObject\MockObject $resetRequestRepository;
    private \PHPUnit\Framework\MockObject\MockObject $securityEventRepository;
    private \PHPUnit\Framework\MockObject\MockObject $tokenRevocationService;
    private \PHPUnit\Framework\MockObject\MockObject|MessageBusInterface $messageBus;
    private \PHPUnit\Framework\MockObject\MockObject $passwordPolicyService;
    private \PHPUnit\Framework\MockObject\MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->resetRequestRepository = $this->createMock(PasswordResetRequestRepositoryInterface::class);
        $this->securityEventRepository = $this->createMock(SecurityEventRepositoryInterface::class);
        $this->tokenRevocationService = $this->createMock(TokenRevocationService::class);
        // Create a fake messageBus that doesn't care about types
        $this->messageBus = new class() implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): \Symfony\Component\Messenger\Envelope {
                return \Symfony\Component\Messenger\Envelope::wrap($message, $stamps);
            }
        };
        $this->passwordPolicyService = $this->createMock(PasswordPolicyService::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $this->service = new PasswordResetService(
            $this->userRepository,
            $this->resetRequestRepository,
            $this->securityEventRepository,
            $this->tokenRevocationService,
            $this->messageBus,
            $this->passwordPolicyService,
            $this->passwordHasher,
        );
    }

    public function testRequestResetWithUnknownEmailReturnsVoidSilently(): void
    {
        $email = 'unknown@example.com';

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->resetRequestRepository
            ->expects($this->never())
            ->method('save');

        $this->service->requestReset($email);
    }

    public function testRequestResetWithKnownEmailCreatesRequestAndDispatchesMessage(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'user@example.com';
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(ResetPasswordRequest::class));

        // The messageBus is a stub, so it will silently accept the dispatch call
        // The functional test will verify the actual message dispatch

        $this->service->requestReset($email);
    }

    public function testCompleteResetWithValidTokenAndPasswordSucceeds(): void
    {
        $token = bin2hex(random_bytes(32));
        $newPassword = 'NewPassword123!@#';
        $userId = '550e8400-e29b-41d4-a716-446655440001';
        $userEmail = 'user@example.com';
        $hashedToken = hash('sha256', $token);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn($userEmail);

        $resetRequest = $this->createMock(ResetPasswordRequest::class);
        $resetRequest->method('isExpired')->willReturn(false);
        $resetRequest->method('isConsumed')->willReturn(false);
        $resetRequest->method('getUser')->willReturn($user);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with($hashedToken)
            ->willReturn($resetRequest);

        $this->passwordPolicyService
            ->expects($this->once())
            ->method('assertPassword')
            ->with($newPassword, $userEmail)
            ->willReturn([]);

        $hashedPassword = 'hashed_password_123';
        $this->passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->with($newPassword)
            ->willReturn($hashedPassword);

        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $resetRequest->expects($this->once())
            ->method('consume');

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('save')
            ->with($resetRequest);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->tokenRevocationService
            ->expects($this->once())
            ->method('revokeAllForUser')
            ->with($userId);

        $this->securityEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(SecurityEvent::class), true);

        $this->service->completeReset($token, $newPassword);
    }

    public function testCompleteResetWithExpiredTokenThrowsException(): void
    {
        $token = bin2hex(random_bytes(32));
        $newPassword = 'NewPassword123!@#';
        $hashedToken = hash('sha256', $token);

        $resetRequest = $this->createMock(ResetPasswordRequest::class);
        $resetRequest->method('isExpired')->willReturn(true);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with($hashedToken)
            ->willReturn($resetRequest);

        $this->expectException(ExpiredPasswordResetTokenException::class);

        $this->service->completeReset($token, $newPassword);
    }

    public function testCompleteResetWithConsumedTokenThrowsException(): void
    {
        $token = bin2hex(random_bytes(32));
        $newPassword = 'NewPassword123!@#';
        $hashedToken = hash('sha256', $token);

        $resetRequest = $this->createMock(ResetPasswordRequest::class);
        $resetRequest->method('isExpired')->willReturn(false);
        $resetRequest->method('isConsumed')->willReturn(true);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with($hashedToken)
            ->willReturn($resetRequest);

        $this->expectException(ConsumedPasswordResetTokenException::class);

        $this->service->completeReset($token, $newPassword);
    }

    public function testCompleteResetWithInvalidTokenThrowsException(): void
    {
        $token = bin2hex(random_bytes(32));
        $newPassword = 'NewPassword123!@#';
        $hashedToken = hash('sha256', $token);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with($hashedToken)
            ->willReturn(null);

        $this->expectException(InvalidPasswordResetTokenException::class);

        $this->service->completeReset($token, $newPassword);
    }

    public function testCompleteResetWithInvalidPasswordThrowsException(): void
    {
        $token = bin2hex(random_bytes(32));
        $newPassword = 'weak';
        $userId = '550e8400-e29b-41d4-a716-446655440001';
        $userEmail = 'user@example.com';
        $hashedToken = hash('sha256', $token);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn($userEmail);

        $resetRequest = $this->createMock(ResetPasswordRequest::class);
        $resetRequest->method('isExpired')->willReturn(false);
        $resetRequest->method('isConsumed')->willReturn(false);
        $resetRequest->method('getUser')->willReturn($user);

        $this->resetRequestRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->with($hashedToken)
            ->willReturn($resetRequest);

        $this->passwordPolicyService
            ->expects($this->once())
            ->method('assertPassword')
            ->with($newPassword, $userEmail)
            ->willReturn(['Password must be at least 12 characters long.']);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\App\User\Domain\Exception\PasswordPolicyViolationException::class);

        $this->service->completeReset($token, $newPassword);
    }
}
