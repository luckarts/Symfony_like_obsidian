<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\Auth\Application\Service\SessionService;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth/sessions/{id}/revoke', name: 'app_auth_revoke_session', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RevokeSessionController extends AbstractController
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {
    }

    public function __invoke(string $id): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        $found = $this->sessionService->revokeSession($securityUser->getUserIdentifier(), $securityUser->getUser()->getId(), $id);

        if (!$found) {
            return new JsonResponse(['error' => 'session not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
