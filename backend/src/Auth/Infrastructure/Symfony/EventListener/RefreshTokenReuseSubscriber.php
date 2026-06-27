<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Symfony\EventListener;

use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use App\User\Domain\Contract\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Detects refresh token reuse and revokes all user tokens.
 */
final class RefreshTokenReuseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenRevocationService $tokenRevocationService,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
        private readonly string $encryptionKey,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getPathInfo() !== '/oauth2/token' || !$request->isMethod('POST')) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() !== 400) {
            return;
        }

        $body = json_decode((string) $response->getContent(), true);
        if (!isset($body['hint']) || $body['hint'] !== 'Token has been revoked') {
            return;
        }

        $encryptedRefreshToken = $request->request->get('refresh_token');
        if (!$encryptedRefreshToken) {
            return;
        }

        try {
            $decrypted = \Defuse\Crypto\Crypto::decryptWithPassword($encryptedRefreshToken, $this->encryptionKey);
            $data = json_decode($decrypted, true, 512, \JSON_THROW_ON_ERROR);

            $refreshTokenId = $data['refresh_token_id'] ?? '';
            $userIdentifier = $data['user_id'] ?? '';

            if (!$refreshTokenId || !$userIdentifier) {
                $this->logger->warning('Refresh token reuse detection: missing token id or user id in decrypted token');
                return;
            }

            $user = $this->userRepository->findByEmail($userIdentifier);
            if (!$user) {
                $this->logger->warning('Refresh token reuse detection: user not found for identifier '.$userIdentifier);
                return;
            }

            $userId = $user->getId();

            // If token was revoked via session endpoint (not rotation reuse), skip revokeAll
            $recentRevoke = $this->securityEventRepository->findRecentRevokedByUserAndReason($userId, 'session_revoke');
            if ($recentRevoke !== null) {
                $this->logger->info('Refresh token reuse skipped: token was legitimately revoked via session endpoint');

                return;
            }

            $ip = $request->getClientIp();
            $userAgent = $request->headers->get('User-Agent');

            $securityEvent = SecurityEvent::tokenReuseDetected($userId, $ip, $userAgent);
            $this->securityEventRepository->save($securityEvent);

            $this->tokenRevocationService->revokeAllForUser($userId);
        } catch (\Throwable $e) {
            $this->logger->warning('Refresh token reuse detection failed: '.$e->getMessage());
        }
    }
}