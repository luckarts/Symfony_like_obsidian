<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Symfony\EventListener;

use App\Auth\Application\Service\TokenRevocationService;
use App\Auth\Domain\Contract\SecurityEventRepositoryInterface;
use App\Auth\Domain\Entity\SecurityEvent;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Detects refresh token reuse and revokes all user tokens.
 */
final class RefreshTokenReuseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenRevocationService $tokenRevocationService,
        private readonly SecurityEventRepositoryInterface $securityEventRepository,
        private readonly LoggerInterface $logger,
        private readonly string $encryptionKey,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof OAuthServerException) {
            return;
        }

        if ($exception->getHint() !== 'Token has been revoked') {
            return;
        }

        $request = $event->getRequest();
        if ($request->getPathInfo() !== '/oauth2/token' || !$request->isMethod('POST')) {
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
            $userId = $data['user_id'] ?? '';

            if (!$refreshTokenId || !$userId) {
                $this->logger->warning('Refresh token reuse detection: missing token id or user id in decrypted token');
                return;
            }

            // Ensure userId is string for consistency
            $userId = (string) $userId;

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

            // Revoke all tokens for user (fire-and-forget)
            $this->tokenRevocationService->revokeAllForUser($userId);

            // Note: We do not rethrow the exception; the original response (invalid_grant) is sent to client.
        } catch (\Throwable $e) {
            // Log decryption or other errors but do not block the response
            $this->logger->warning('Refresh token reuse detection failed: '.$e->getMessage());
        }
    }
}