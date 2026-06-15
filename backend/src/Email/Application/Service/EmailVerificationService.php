<?php

declare(strict_types=1);

namespace App\Email\Application\Service;

use App\User\Domain\Contract\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationService
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function verify(string $userId, string $userEmail, Request $request): void
    {
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new \RuntimeException('User not found.');
        }

        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, $userId, $userEmail);

        $user->verify();
        $this->userRepository->save($user);
    }
}
