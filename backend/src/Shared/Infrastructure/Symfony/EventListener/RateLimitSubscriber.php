<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Shared\Infrastructure\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactoryInterface $apiDefaultLimiter,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Path matching on getPathInfo()
        if (!str_starts_with($request->getPathInfo(), '/api/v1/')) {
            return;
        }

        // Exclusion by route name
        $routeName = $request->attributes->get('_route', '');
        if (str_starts_with($routeName, 'oauth2_') || str_starts_with($routeName, 'league_oauth2_server')) {
            return;
        }

        // Determine key
        $ip = $request->getClientIp();
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() instanceof UserInterface) {
            $key = $ip . '|' . $token->getUser()->getUserIdentifier();
        } else {
            $key = $ip;
        }

        $limiter = $this->apiDefaultLimiter->create($key);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(
                max(0, $limit->getRetryAfter()->getTimestamp() - time()),
                'Too many requests. Please wait before retrying.'
            );
        }
    }
}