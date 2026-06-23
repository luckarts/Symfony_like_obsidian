<?php

declare(strict_types=1);

namespace App\Tests\E2E\Email;

use App\Tests\E2E\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

#[Group('e2e')]
#[Group('email')]
class EmailVerificationTest extends AbstractApiTestCase
{
    private const TEST_PASSWORD = 'T3st!P@ss#Api42';

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $email = 'verify-' . uniqid() . '@example.com';
        $this->token = $this->authenticate($email, self::TEST_PASSWORD);
    }

    #[Test]
    #[Group('smoke')]
    public function resend_verification_email(): void
    {
        $response = $this->apiRequest('POST', '/api/email/resend-verification', $this->token);
        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    #[Test]
    public function resend_verification_without_auth_returns_401(): void
    {
        $response = $this->apiRequest('POST', '/api/email/resend-verification');
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    #[Test]
    public function verify_with_missing_params_returns_400(): void
    {
        $this->client->request('GET', '/api/email/verify');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
