<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenTest extends AbstractApiTestCase
{
    private const PASSWORD = 'T3st!P@ss#Api42';
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function loginWithRefreshToken(string $email, string $password): array
    {
        $this->setUpApiTestHelper();

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email',
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var array{access_token: string, refresh_token: string} $data */
        $data = json_decode($response->getContent(), true);

        return $data;
    }

    #[Test]
    #[Group('smoke')]
    #[Group('e2e')]
    #[Group('user')]
    public function refresh_token_grant_returns_new_access_token(): void
    {
        $email = 'refresh_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);
        $this->assertArrayHasKey('refresh_token', $tokens);

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
            'scope' => 'email',
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var array{access_token: string, refresh_token: string} $data */
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertNotSame($tokens['access_token'], $data['access_token']);
        $this->assertNotSame($tokens['refresh_token'], $data['refresh_token']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function revoked_refresh_token_is_rejected_on_refresh(): void
    {
        $email = 'refresh_revoked_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokens['access_token'],
            ['refresh_token' => $tokens['refresh_token']],
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
            'scope' => 'email',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function invalid_refresh_token_is_rejected(): void
    {
        $this->setUpApiTestHelper();

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => 'not-a-valid-refresh-token',
            'scope' => 'email',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
