<?php

declare(strict_types=1);

namespace App\Tests\E2E\Security;

use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Infrastructure\Doctrine\DoctrineSecurityEventRepository;
use App\Tests\E2E\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

#[Group('e2e')]
#[Group('auth')]
class RefreshTokenHardeningTest extends AbstractApiTestCase
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
    public function refresh_token_rotation_invalidates_old_token(): void
    {
        $email = 'rotation_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        // Login -> get tokens
        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);
        $oldRefreshToken = $tokens['refresh_token'];

        // Refresh -> consumes old token, issues new ones
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $oldRefreshToken,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Use old token again -> must be rejected (already consumed by rotation)
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $oldRefreshToken,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertSame('invalid_grant', $content['error']);
    }

    #[Test]
    public function refresh_token_reuse_logs_token_reuse_detected(): void
    {
        $email = 'reuse_event_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        // Login -> get tokens
        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);
        $oldRefreshToken = $tokens['refresh_token'];

        // Refresh -> consumes old token
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $oldRefreshToken,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Re-submit old token -> 400 + triggers TOKEN_REUSE_DETECTED event
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $oldRefreshToken,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        // Query SecurityEvent repository for TOKEN_REUSE_DETECTED with this user
        /** @var DoctrineSecurityEventRepository $repository */
        $repository = static::getContainer()->get(DoctrineSecurityEventRepository::class);
        $events = $repository->findBy(
            ['userId' => $user->getId(), 'eventType' => SecurityEventType::TOKEN_REUSE_DETECTED],
            ['id' => 'DESC'],
        );

        $this->assertNotEmpty($events, 'Expected at least one TOKEN_REUSE_DETECTED event');
        $this->assertSame(SecurityEventType::TOKEN_REUSE_DETECTED, $events[0]->getEventType());
    }

    #[Test]
    public function logout_revokes_refresh_token(): void
    {
        $email = 'logout_revoke_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        // Login -> get tokens
        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        // Logout with refresh_token -> 204
        $response = $this->apiRequest(
            'POST',
            '/api/v1/auth/logout',
            $tokens['access_token'],
            ['refresh_token' => $tokens['refresh_token']],
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Refresh with the same token -> 400 (revoked)
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
    public function list_sessions_returns_active_sessions(): void
    {
        $email = 'sessions_isolation_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        // Login user A
        $tokens = $this->loginWithRefreshToken($email, self::PASSWORD);

        // List sessions
        $response = $this->apiRequest('GET', '/api/v1/auth/sessions', $tokens['access_token']);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $sessions = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($sessions);
        $this->assertNotEmpty($sessions);

        // Each session has required fields
        foreach ($sessions as $session) {
            $this->assertArrayHasKey('id', $session);
            $this->assertArrayHasKey('expires_at', $session);
            $this->assertSame(16, \strlen($session['id']));
        }

        // Login user B -> list must NOT contain user A sessions
        $emailB = 'sessions_isolation_b_' . uniqid() . '@example.com';
        $this->createUser($emailB, self::PASSWORD, 'Bob', 'Smith');
        $tokensB = $this->loginWithRefreshToken($emailB, self::PASSWORD);

        $responseB = $this->apiRequest('GET', '/api/v1/auth/sessions', $tokensB['access_token']);
        $this->assertSame(Response::HTTP_OK, $responseB->getStatusCode());

        $sessionsB = json_decode((string) $responseB->getContent(), true);

        // User B sessions IDs must not overlap with user A session IDs
        $userASessionIds = array_map(fn (array $s) => $s['id'], $sessions);
        $userBSessionIds = array_map(fn (array $s) => $s['id'], $sessionsB);
        $overlap = array_intersect($userASessionIds, $userBSessionIds);
        $this->assertEmpty($overlap, 'User B must not see user A sessions');
    }

    #[Test]
    public function revoke_specific_session(): void
    {
        $email = 'revoke_specific_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD, 'Jane', 'Doe');

        // First login -> session 1
        $tokens1 = $this->loginWithRefreshToken($email, self::PASSWORD);

        // List sessions to get first session ID
        $response = $this->apiRequest('GET', '/api/v1/auth/sessions', $tokens1['access_token']);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $sessionsAfterFirstLogin = json_decode((string) $response->getContent(), true);
        $sessionId1 = $sessionsAfterFirstLogin[0]['id'];

        // Second login -> session 2
        $tokens2 = $this->loginWithRefreshToken($email, self::PASSWORD);

        // Revoke first session
        $response = $this->apiRequest(
            'POST',
            "/api/v1/auth/sessions/{$sessionId1}/revoke",
            $tokens2['access_token'],
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Refresh with first token -> 400 (revoked)
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens1['refresh_token'],
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        // Refresh with second token -> 200 (still valid)
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens2['refresh_token'],
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
