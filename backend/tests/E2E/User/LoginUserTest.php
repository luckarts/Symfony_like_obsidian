<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LoginUserTest extends WebTestCase
{
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        /** @var ClientManagerInterface $clientManager */
        $clientManager = static::getContainer()->get(ClientManagerInterface::class);
        $oauthClient = new Client('Test Client', self::CLIENT_ID, self::CLIENT_SECRET);
        $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
        $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
        $clientManager->save($oauthClient);
    }

    #[Test]
    #[Group('smoke')]
    #[Group('e2e')]
    #[Group('user')]
    public function login_with_valid_credentials_returns_access_token(): void
    {
        $email = 'login_' . uniqid() . '@example.com';
        $password = 'password123';

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => $password, 'firstName' => 'Jane', 'lastName' => 'Doe']),
        );
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            '/oauth2/token',
            [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => $password,
                'scope' => 'email',
            ],
        );
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var array{access_token: string, token_type: string, expires_in: int} $data */
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertSame('Bearer', $data['token_type']);
        $this->assertGreaterThan(0, $data['expires_in']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function login_with_wrong_password_fails(): void
    {
        $email = 'login_wrong_' . uniqid() . '@example.com';

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => 'password123', 'firstName' => 'Jane', 'lastName' => 'Doe']),
        );
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            '/oauth2/token',
            [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => 'wrong_password',
                'scope' => 'email',
            ],
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
