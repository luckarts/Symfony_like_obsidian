<?php

declare(strict_types=1);

namespace App\Email\Application\Service;

use App\Email\Application\Message\SendVerificationEmailMessage;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationService
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function verify(string $userId, string $userEmail, Request $request): void
    {
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw UserNotFoundException::withId($userId);
        }

        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, $userId, $userEmail);

        $user->verify();
        $this->userRepository->save($user);
    }

    public function sendVerificationEmail(string $userId): void
    {
        $this->eventBus->dispatch(new SendVerificationEmailMessage($userId));
    }
}
