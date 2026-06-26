<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\User\Application\Service\PasswordResetService;
use App\User\Domain\Exception\ConsumedPasswordResetTokenException;
use App\User\Domain\Exception\ExpiredPasswordResetTokenException;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use App\User\Domain\Exception\PasswordPolicyViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class ResetPasswordController
{
    public function __construct(
        private readonly PasswordResetService $service,
    ) {
    }

    #[Route('/api/auth/password-reset/reset', name: 'password_reset_complete', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse(['error' => 'Invalid request body'], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['token']) || !isset($data['password'])) {
                return new JsonResponse(['error' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
            }

            $token = $data['token'];
            $password = $data['password'];

            if (!is_string($token) || !is_string($password)) {
                return new JsonResponse(['error' => 'Token and password must be strings'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $this->service->completeReset($token, $password);

                return new JsonResponse(['message' => 'Password reset completed successfully'], Response::HTTP_OK);
            } catch (InvalidPasswordResetTokenException | ExpiredPasswordResetTokenException | ConsumedPasswordResetTokenException) {
                return new JsonResponse(['error' => 'Invalid or expired reset token'], Response::HTTP_BAD_REQUEST);
            } catch (PasswordPolicyViolationException $e) {
                return new JsonResponse(['error' => 'Password validation failed', 'violations' => [$e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
    }
}
