<?php

declare(strict_types=1);

namespace App\Email\Infrastructure\Controller;

use App\Email\Application\Service\EmailVerificationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

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
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid or expired verification link.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
