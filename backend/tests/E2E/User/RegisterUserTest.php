<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RegisterUserTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    #[Test]
    #[Group('smoke')]
    #[Group('e2e')]
    #[Group('user')]
    public function register_success(): void
    {
        $headers = ['CONTENT_TYPE' => 'application/ld+json'];
        $payload = [
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];
        $response = $this->client->request('POST', '/api/users',[], [], $headers, json_encode($payload));
        $response = $this->client->getResponse();
       
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $data = json_decode($response->getContent(), true);
        // Assertions sur le contenu
        $this->assertArrayHasKey('email', $data);
        $this->assertSame($payload['email'], $data['email']);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('user')]
    public function register_with_duplicate_email_returns_409(): void
    {
        $headers = ['CONTENT_TYPE' => 'application/ld+json'];
        $payload = [
            'email' => 'duplicate_' . uniqid() . '@example.com',
            'password' => 'T3st!P@ss#Api42',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $this->client->request('POST', '/api/users', [], [], $headers, json_encode($payload));
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/users', [], [], $headers, json_encode($payload));
        $this->assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
    }
}
