<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class LogoutUserTest extends AbstractApiTestCase
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
    public function logout_revokes_refresh_token(): void
    {
        $email = 'logout_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokens['access_token'],
            ['refresh_token' => $tokens['refresh_token']],
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Refresh token is now revoked: refreshing must fail.
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
    public function logout_revokes_access_token(): void
    {
        $email = 'logout_access_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokens['access_token'],
            ['refresh_token' => $tokens['refresh_token']],
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Access token is now revoked: any authenticated call must fail.
        $this->apiRequest('GET', '/api/users/{id}', $tokens['access_token']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function logout_with_invalid_refresh_token_returns_400(): void
    {
        $email = 'logout_invalid_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokens['access_token'],
            ['refresh_token' => 'not-a-valid-refresh-token'],
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function logout_with_another_users_refresh_token_returns_403(): void
    {
        $emailA = 'logout_a_' . uniqid() . '@example.com';
        $emailB = 'logout_b_' . uniqid() . '@example.com';
        $this->createUser($emailA, self::PASSWORD, 'Alice', 'A');
        $this->createUser($emailB, self::PASSWORD, 'Bob', 'B');

        $tokensA = $this->loginWithRefreshToken($emailA, self::PASSWORD);
        $tokensB = $this->loginWithRefreshToken($emailB, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokensB['access_token'],
            ['refresh_token' => $tokensA['refresh_token']],
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function logout_without_token_returns_401(): void
    {
        $response = $this->apiRequest('POST', '/api/v1/auth/logout', null, ['refresh_token' => 'whatever']);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
