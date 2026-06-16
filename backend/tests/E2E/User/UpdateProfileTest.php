<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\E2E\AbstractApiTestCase;

class UpdateProfileTest extends AbstractApiTestCase
{
    #[Test]
    #[Group('smoke')]
    #[Group('e2e')]
    #[Group('user')]
    public function update_profile_success(): void
    {
        $email = 'update_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $user = $this->createUser($email, $password, 'John', 'Doe');
        $token = $this->getOAuth2Token($email, $password);

        $response = $this->apiRequest('PUT', sprintf('/api/users/%s/profile', $user->getId()), $token, [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Updated', $data['firstName']);
        $this->assertSame('Name', $data['lastName']);
        $this->assertSame($email, $data['email']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function update_profile_without_token_returns_401(): void
    {
        $email = 'update_noauth_' . uniqid() . '@example.com';
        $user = $this->createUser($email, 'T3st!P@ss#Api42', 'John', 'Doe');

        $response = $this->apiRequest('PUT', sprintf('/api/users/%s/profile', $user->getId()), null, [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
