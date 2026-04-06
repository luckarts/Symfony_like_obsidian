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

class GetProfileTest extends WebTestCase
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
    public function get_profile_returns_authenticated_user_data(): void
    {
        $email = 'profile_' . uniqid() . '@example.com';
        $password = 'password123';

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => $password, 'firstName' => 'Alice', 'lastName' => 'Smith']),
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
        /** @var array{access_token: string} $tokenData */
        $tokenData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $tokenData['access_token'];

        $this->client->request(
            'GET',
            '/api/user/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var array{id: string, email: string, firstName: string, lastName: string, roles: list<string>} $data */
        $data = json_decode($response->getContent(), true);
        $this->assertSame($email, $data['email']);
        $this->assertSame('Alice', $data['firstName']);
        $this->assertSame('Smith', $data['lastName']);
        $this->assertContains('ROLE_USER', $data['roles']);
        $this->assertNotEmpty($data['id']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function get_profile_without_token_returns_401(): void
    {
        $this->client->request('GET', '/api/user/profile');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function expired_token_returns_401(): void
    {
        $email = 'profile_expired_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->createUser($email, $password, 'Alice', 'Smith');
        $token = $this->getOAuth2Token($email, $password);

        // access_token_ttl is overridden to PT1S in when@test config.
        sleep(2);

        $this->apiRequest('GET', '/api/users/{id}', $token);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
