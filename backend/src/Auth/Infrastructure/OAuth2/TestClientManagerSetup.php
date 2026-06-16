<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\OAuth2;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\ClientManager;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class TestClientManagerSetup
{
    private bool $initialized = false;

    public function __construct(
        private readonly ClientManager $clientManager,
        #[Autowire(env: 'OAUTH_DEFAULT_CLIENT_ID')]
        private readonly string $clientId,
        #[Autowire(env: 'OAUTH_DEFAULT_CLIENT_SECRET')]
        private readonly string $clientSecret,
    ) {
    }

    public function setupTestClient(RequestEvent $event): void
    {
        if ($this->initialized) {
            return;
        }

        if ($this->clientManager->find($this->clientId) !== null) {
            $this->initialized = true;

            return;
        }

        $client = new Client(
            name: 'Test Client',
            identifier: $this->clientId,
            secret: $this->clientSecret,
        );

        $client->setGrants(
            new Grant('password'),
            new Grant('client_credentials'),
            new Grant('refresh_token'),
        );

        $client->setScopes(
            new Scope('email'),
            new Scope('profile'),
            new Scope('read'),
            new Scope('write'),
        );

        $this->clientManager->save($client);
        $this->initialized = true;
    }
}