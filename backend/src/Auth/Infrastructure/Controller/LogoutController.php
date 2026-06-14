<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Controller;

use App\User\Infrastructure\Security\SecurityUser;
use Defuse\Crypto\Crypto;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AccessTokenManagerInterface $accessTokenManager,
        #[Autowire(env: 'OAUTH_ENCRYPTION_KEY')]
        private readonly string $encryptionKey,
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
            $decrypted = Crypto::decryptWithPassword($refreshTokenString, $this->encryptionKey);
            /** @var array{refresh_token_id?: string} $data */
            $data = json_decode($decrypted, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'invalid refresh_token'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->refreshTokenManager->find($data['refresh_token_id'] ?? '');

        if ($refreshToken === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $accessToken = $refreshToken->getAccessToken();

        if ($accessToken === null || $accessToken->getUserIdentifier() !== $securityUser->getUserIdentifier()) {
            return new JsonResponse(['error' => 'refresh_token does not belong to current user'], Response::HTTP_FORBIDDEN);
        }

        if (!$refreshToken->isRevoked()) {
            $this->refreshTokenManager->save($refreshToken->revoke());
        }

        if (!$accessToken->isRevoked()) {
            $this->accessTokenManager->save($accessToken->revoke());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
