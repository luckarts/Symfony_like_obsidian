<?php

declare(strict_types=1);

namespace App\Tests\E2E\Auth;

use App\Tests\E2E\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class SessionTest extends AbstractApiTestCase
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
    #[Group('auth')]
    public function list_sessions_returns_active_sessions(): void
    {
        $email = 'sessions_list_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'GET',
            '/api/v1/auth/sessions',
            $tokens['access_token'],
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('expires_at', $data[0]);
        $this->assertSame(16, \strlen($data[0]['id']));
    }

    #[Test]
    #[Group('e2e')]
    #[Group('auth')]
    public function list_sessions_unauthorized_without_token(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/auth/sessions', null);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('auth')]
    public function revoke_session_revokes_specific_session(): void
    {
        $email = 'sessions_revoke_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        // Get sessions list
        $response = $this->apiRequest('GET', '/api/v1/auth/sessions', $tokens['access_token']);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $sessions = json_decode((string) $response->getContent(), true);
        $sessionId = $sessions[0]['id'];

        // Revoke the session
        $response = $this->apiRequest(
            'POST',
            "/api/v1/auth/sessions/{$sessionId}/revoke",
            $tokens['access_token'],
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Refresh token is now revoked: refreshing must fail
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
    #[Group('auth')]
    public function revoke_session_not_found_returns_404(): void
    {
        $email = 'sessions_notfound_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/sessions/nonexistentid12345/revoke',
            $tokens['access_token'],
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('auth')]
    public function revoke_session_unauthorized_without_token(): void
    {
        $response = $this->apiRequest('POST', '/api/v1/auth/sessions/someid/revoke', null);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('auth')]
    public function logout_all_revokes_all_sessions(): void
    {
        $email = 'sessions_logout_all_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout/all',
            $tokens['access_token'],
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Refresh token is now revoked
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
}
