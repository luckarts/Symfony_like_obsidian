<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Auth\Domain\Exception\RefreshTokenOwnershipException;
use Defuse\Crypto\Crypto;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class LogoutService
{
    public function __construct(
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AccessTokenManagerInterface $accessTokenManager,
        #[Autowire(env: 'OAUTH_ENCRYPTION_KEY')]
        private readonly string $encryptionKey,
    ) {
    }

    /**
     * Revoke refresh token and its associated access token.
     *
     * @param string $userIdentifier The identifier of the authenticated user
     * @param string $encryptedRefreshToken The encrypted refresh token from the request
     *
     * @throws InvalidRefreshTokenException When the token cannot be decrypted or parsed
     * @throws RefreshTokenOwnershipException When the token belongs to another user
     */
    public function revoke(string $userIdentifier, string $encryptedRefreshToken): void
    {
        $refreshTokenId = $this->decryptRefreshTokenId($encryptedRefreshToken);

        $refreshToken = $this->refreshTokenManager->find($refreshTokenId);

        if ($refreshToken === null) {
            return;
        }

        $accessToken = $refreshToken->getAccessToken();

        if ($accessToken === null || $accessToken->getUserIdentifier() !== $userIdentifier) {
            throw new RefreshTokenOwnershipException();
        }

        if (!$refreshToken->isRevoked()) {
            $this->refreshTokenManager->save($refreshToken->revoke());
        }

        if (!$accessToken->isRevoked()) {
            $this->accessTokenManager->save($accessToken->revoke());
        }
    }

    /**
     * @throws InvalidRefreshTokenException
     */
    private function decryptRefreshTokenId(string $encryptedRefreshToken): string
    {
        try {
            $decrypted = Crypto::decryptWithPassword($encryptedRefreshToken, $this->encryptionKey);

            /** @var array{refresh_token_id?: string} $data */
            $data = json_decode($decrypted, true, 512, \JSON_THROW_ON_ERROR);

            return $data['refresh_token_id'] ?? '';
        } catch (\Throwable) {
            throw new InvalidRefreshTokenException();
        }
    }
}
