<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Service\LogoutService;
use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Auth\Domain\Exception\RefreshTokenOwnershipException;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth/logout', name: 'app_auth_logout', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly LogoutService $logoutService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        $payload = json_decode($request->getContent(), true);
        $refreshTokenString = \is_array($payload) ? ($payload['refresh_token'] ?? null) : null;

        if (!\is_string($refreshTokenString) || $refreshTokenString === '') {
            return new JsonResponse(['error' => 'refresh_token is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->logoutService->revoke($securityUser->getUserIdentifier(), $refreshTokenString);
        } catch (InvalidRefreshTokenException) {
            return new JsonResponse(['error' => 'invalid refresh_token'], Response::HTTP_BAD_REQUEST);
        } catch (RefreshTokenOwnershipException) {
            return new JsonResponse(['error' => 'refresh_token does not belong to current user'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
