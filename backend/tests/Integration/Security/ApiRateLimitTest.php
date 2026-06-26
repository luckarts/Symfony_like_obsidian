<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
#[Group('security')]
class ApiRateLimitTest extends WebTestCase
{
    private const CLIENT_ID = 'test_api_rate_limit';
    private const CLIENT_SECRET = 'test_secret_api_rate_limit';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $clientManager = static::getContainer()->get(ClientManagerInterface::class);
        if ($clientManager->find(self::CLIENT_ID) === null) {
            $oauthClient = new Client('ApiRateLimitTest', self::CLIENT_ID, self::CLIENT_SECRET);
            $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
            $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
            $clientManager->save($oauthClient);
        }
    }

    private function obtainToken(): string
    {
        $email = 'api_rate_limit_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        // Register user
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => $password, 'firstName' => 'Jane', 'lastName' => 'Doe']),
        );

        // Obtain token
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email profile',
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        return $data['access_token'];
    }

    #[Test]
    public function api_v1_route_returns_429_after_limit_exceeded(): void
    {
        $token = $this->obtainToken();

        // api_default in test env: 5 req / 10s sliding window
        $limit = 5;

        // First $limit requests: should all succeed
        for ($i = 0; $i < $limit; $i++) {
            $this->client->request('GET', '/api/v1/security/events', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]);
            $this->assertNotSame(
                Response::HTTP_TOO_MANY_REQUESTS,
                $this->client->getResponse()->getStatusCode(),
                'Request ' . ($i + 1) . ' should not be rate limited yet',
            );
        }

        // Request $limit + 1: should be 429 with Retry-After header
        $this->client->request('GET', '/api/v1/security/events', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'), 'Retry-After header must be present');
    }

    #[Test]
    public function oauth2_token_route_not_affected_by_generic_rate_limiter(): void
    {
        // Hit /oauth2/token a few times to prove the generic RateLimitSubscriber
        // does NOT match this route. Stay below login_ip limit (3) to avoid
        // login-specific rate limiting (tested separately in S6).
        $attempts = 2;

        for ($i = 0; $i < $attempts; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => 'nonexistent_' . $i . '@test.com',
                'password' => 'bad_password',
            ]);

            $statusCode = $this->client->getResponse()->getStatusCode();

            // Response must NOT be 429 from the generic API rate limiter.
            $this->assertNotSame(
                Response::HTTP_TOO_MANY_REQUESTS,
                $statusCode,
                'Request ' . ($i + 1) . ' to /oauth2/token should not trigger generic rate limiter',
            );
        }
    }
}
