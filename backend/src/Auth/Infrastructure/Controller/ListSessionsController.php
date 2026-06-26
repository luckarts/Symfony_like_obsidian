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

#[Route('/api/v1/auth/sessions', name: 'app_auth_list_sessions', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ListSessionsController extends AbstractController
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {
    }

    public function __invoke(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();

        $sessions = $this->sessionService->listSessions($securityUser->getUserIdentifier());

        $data = array_map(fn ($session) => [
            'id' => $session->id,
            'expires_at' => $session->expiresAt->format(\DateTimeInterface::ATOM),
        ], $sessions);

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
