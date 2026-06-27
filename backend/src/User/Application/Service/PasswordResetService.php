<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\User\Application\Message\SendPasswordResetEmailMessage;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\PasswordResetRequestRepositoryInterface;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\ResetPasswordRequest;
use App\User\Domain\Exception\ConsumedPasswordResetTokenException;
use App\User\Domain\Exception\ExpiredPasswordResetTokenException;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use App\User\Domain\Exception\PasswordPolicyViolationException;
use App\User\Domain\Service\PasswordPolicyService;
use Symfony\Component\Messenger\MessageBusInterface;

class PasswordResetService
{
    public const TOKEN_EXPIRATION_HOURS = 24;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetRequestRepositoryInterface $resetRequestRepository,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
        private readonly TokenRevocationService $tokenRevocationService,
        private readonly MessageBusInterface $messageBus,
        private readonly PasswordPolicyService $passwordPolicyService,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Request a password reset for the given email.
     * Returns silently to prevent email enumeration.
     */
    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (null === $user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = new \DateTimeImmutable('+ '.self::TOKEN_EXPIRATION_HOURS.' hours');

        $resetRequest = new ResetPasswordRequest($user, $tokenHash, $expiresAt);
        $this->resetRequestRepository->save($resetRequest);

        $event = SecurityEvent::passwordResetRequested($user->getId(), null, null);
        $this->securityEventRepository->save($event, flush: true);

        $this->messageBus->dispatch(new SendPasswordResetEmailMessage($user->getId(), $token));
    }

    /**
     * Complete the password reset process.
     *
     * @throws InvalidPasswordResetTokenException If token is not found
     * @throws ExpiredPasswordResetTokenException If token has expired
     * @throws ConsumedPasswordResetTokenException If token has already been used
     * @throws PasswordPolicyViolationException If password validation fails
     */
    public function completeReset(string $token, string $newPassword): void
    {
        $hashedToken = hash('sha256', $token);

        $resetRequest = $this->resetRequestRepository->findByTokenHash($hashedToken);

        if (null === $resetRequest) {
            throw InvalidPasswordResetTokenException::create();
        }

        if ($resetRequest->isExpired()) {
            throw ExpiredPasswordResetTokenException::create();
        }

        if ($resetRequest->isConsumed()) {
            throw ConsumedPasswordResetTokenException::create();
        }

        $user = $resetRequest->getUser();

        $violations = $this->passwordPolicyService->assertPassword($newPassword, $user->getEmail());
        if (!empty($violations)) {
            throw new PasswordPolicyViolationException(implode(' ', $violations));
        }

        $user->setPassword($this->passwordHasher->hash($newPassword));
        $resetRequest->consume();

        $this->resetRequestRepository->save($resetRequest);
        $this->userRepository->save($user);

        $this->tokenRevocationService->revokeAllForUser($user->getId());

        $event = SecurityEvent::passwordResetCompleted($user->getId(), null, null);
        $this->securityEventRepository->save($event, flush: true);
    }
}
