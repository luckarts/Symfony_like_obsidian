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

#[Route('/api/v1/auth/logout/all', name: 'app_auth_logout_all', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RevokeAllSessionsController extends AbstractController
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {
    }

    public function __invoke(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        $this->sessionService->revokeAll($securityUser->getUserIdentifier(), $securityUser->getUser()->getId());

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
