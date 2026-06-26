<?php

declare(strict_types=1);

namespace App\Tests\Functional\User\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RequestPasswordResetControllerTest extends WebTestCase
{
    public function testPostWithValidEmailReturns204(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/password-reset/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'user@example.com',
        ]));

        $this->assertResponseStatusCodeSame(204);
    }

    public function testPostWithInvalidEmailReturns204EnumerationPrevention(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/password-reset/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nonexistent@example.com',
        ]));

        // Should return 204 even if user doesn't exist
        $this->assertResponseStatusCodeSame(204);
    }

    public function testPostWithMalformedBodyReturns400(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/password-reset/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid json}');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPostWithoutEmailFieldReturns400(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/password-reset/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
    }
}
