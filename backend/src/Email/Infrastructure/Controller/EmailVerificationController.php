<?php

declare(strict_types=1);

namespace App\Email\Infrastructure\Controller;

use App\Email\Application\Service\EmailVerificationService;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[AsController]
class EmailVerificationController
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    #[Route('/api/email/verify', name: 'api_email_verify', methods: ['GET'])]
    public function verify(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $userEmail = $request->query->get('userEmail');

        if (null === $userId || null === $userEmail) {
            return new JsonResponse(['error' => 'Missing verification parameters.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->emailVerificationService->verify((string) $userId, (string) $userEmail, $request);

            return new JsonResponse(['message' => 'Email verified successfully.'], Response::HTTP_OK);
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (VerifyEmailExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getReason()], Response::HTTP_BAD_REQUEST);
        }
    }
}
