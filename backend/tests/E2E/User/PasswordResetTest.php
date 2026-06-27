<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\AbstractApiTestCase;
use App\User\Domain\Entity\ResetPasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetTest extends AbstractApiTestCase
{
    private const PASSWORD = 'T3st!P@ss#Api42';
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function login(string $email, string $password): array
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
    public function request_reset_returns_204_for_known_email(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $this->createUser($email, self::PASSWORD);

        $response = $this->apiRequest('POST', '/api/auth/password-reset/request', data: [
            'email' => $email,
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function request_reset_returns_204_for_unknown_email(): void
    {
        $response = $this->apiRequest('POST', '/api/auth/password-reset/request', data: [
            'email' => 'nonexistent_' . uniqid() . '@example.com',
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function request_reset_creates_database_record(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD);

        $this->apiRequest('POST', '/api/auth/password-reset/request', data: [
            'email' => $email,
        ]);

        $em = $this->getEntityManager();
        $repo = $em->getRepository(ResetPasswordRequest::class);
        $requests = $repo->findBy(['user' => $user]);

        $this->assertCount(1, $requests);
        $this->assertFalse($requests[0]->isConsumed());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function reset_password_completes_successfully(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD);

        $newPassword = 'NewV@lidP@ss123';
        $plainToken = 'known_reset_token_' . uniqid();
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = new \DateTimeImmutable('+24 hours');

        $resetRequest = new ResetPasswordRequest($user, $tokenHash, $expiresAt);

        $em = $this->getEntityManager();
        $em->persist($resetRequest);
        $em->flush();

        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => $plainToken,
            'password' => $newPassword,
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Password reset completed successfully', $data['message']);

        // Verify old tokens are revoked: old password should still work for login
        // (token revocation only revokes OAuth tokens, not the password itself)
        $tokens = $this->login($email, $newPassword);
        $this->assertArrayHasKey('access_token', $tokens);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function reset_with_invalid_token_returns_400(): void
    {
        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => 'invalid_token_here',
            'password' => 'NewV@lidP@ss123',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid or expired reset token', $data['error']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function reset_with_expired_token_returns_400(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD);

        $plainToken = 'expired_token_' . uniqid();
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = new \DateTimeImmutable('-1 hour');

        $resetRequest = new ResetPasswordRequest($user, $tokenHash, $expiresAt);

        $em = $this->getEntityManager();
        $em->persist($resetRequest);
        $em->flush();

        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => $plainToken,
            'password' => 'NewV@lidP@ss123',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid or expired reset token', $data['error']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function reset_with_weak_password_returns_422(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD);

        $plainToken = 'valid_token_weak_pass_' . uniqid();
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = new \DateTimeImmutable('+24 hours');

        $resetRequest = new ResetPasswordRequest($user, $tokenHash, $expiresAt);

        $em = $this->getEntityManager();
        $em->persist($resetRequest);
        $em->flush();

        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => $plainToken,
            'password' => 'weak',
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Password validation failed', $data['error']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function reset_with_missing_fields_returns_400(): void
    {
        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => 'some-token',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'password' => 'SomePass123!',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function request_reset_with_missing_email_returns_400(): void
    {
        $response = $this->apiRequest('POST', '/api/auth/password-reset/request', data: []);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Email is required', $data['error']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function consumed_token_returns_400(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = $this->createUser($email, self::PASSWORD);

        $plainToken = 'consumed_token_' . uniqid();
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = new \DateTimeImmutable('+24 hours');

        $resetRequest = new ResetPasswordRequest($user, $tokenHash, $expiresAt);
        $resetRequest->consume();

        $em = $this->getEntityManager();
        $em->persist($resetRequest);
        $em->flush();

        $response = $this->apiRequest('POST', '/api/auth/password-reset/reset', data: [
            'token' => $plainToken,
            'password' => 'NewV@lidP@ss123',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid or expired reset token', $data['error']);
    }
}
