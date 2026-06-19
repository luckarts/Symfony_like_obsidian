<?php

declare(strict_types=1);

namespace App\Email\Infrastructure\Controller;

use App\Email\Application\Service\EmailVerificationService;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ResendVerificationController
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    #[Route('/api/email/resend-verification', methods: ['POST'])]
    public function __invoke(#[CurrentUser] ?SecurityUser $securityUser): Response
    {
        if (null === $securityUser) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $securityUser->getUser();

        if ($user->isVerified()) {
            return new JsonResponse(['error' => 'Email already verified.'], Response::HTTP_CONFLICT);
        }

        $this->emailVerificationService->sendVerificationEmail((string) $user->getId());

        return new JsonResponse(['message' => 'Verification email sent.'], Response::HTTP_ACCEPTED);
    }
}
