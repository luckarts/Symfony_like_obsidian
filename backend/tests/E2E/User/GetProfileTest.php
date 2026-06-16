<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\E2E\AbstractApiTestCase;

class GetProfileTest extends AbstractApiTestCase
{

    #[Test]
    #[Group('smoke')]
    #[Group('e2e')]
    #[Group('user')]
    public function get_profile_returns_authenticated_user_data(): void
    {
        $email = 'profile_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->createUser($email, $password, 'Alice', 'Smith');
        $token = $this->getOAuth2Token($email, $password);

        $this->apiRequest(
            'GET',
            '/api/users/{id}',
            $token
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
        $this->apiRequest('GET', '/api/users/{id}');

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
