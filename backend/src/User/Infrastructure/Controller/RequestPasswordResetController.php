<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\User\Application\Service\PasswordResetService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class RequestPasswordResetController
{
    public function __construct(
        private readonly PasswordResetService $service,
    ) {
    }

    #[Route('/api/auth/password-reset/request', name: 'password_reset_request', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data) || !isset($data['email'])) {
                return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
            }

            $email = $data['email'];

            if (!is_string($email)) {
                return new JsonResponse(['error' => 'Email must be a string'], Response::HTTP_BAD_REQUEST);
            }

            $this->service->requestReset($email);

            // Always return 204 to prevent email enumeration
            return new Response(status: Response::HTTP_NO_CONTENT);
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
    }
}
