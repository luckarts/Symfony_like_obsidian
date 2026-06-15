<?php

declare(strict_types=1);

namespace App\Tests\E2E\Security;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CorsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[Test]
    #[Group('e2e')]
    #[Group('security')]
    public function preflight_returns_cors_headers_for_dev_origin(): void
    {
        $this->client->request(
            'OPTIONS',
            '/users',
            server: [
                'HTTP_ORIGIN' => 'http://localhost:3000',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            ],
        );

        $response = $this->client->getResponse();
        $this->assertNotNull(
            $response->headers->get('Access-Control-Allow-Origin'),
            \sprintf(
                'CORS header missing. Status: %d, Headers: %s, Body: %s',
                $response->getStatusCode(),
                json_encode($response->headers->all()),
                $response->getContent(),
            ),
        );
    }

    #[Test]
    #[Group('e2e')]
    #[Group('security')]
    public function preflight_allows_if_match_header(): void
    {
        $this->client->request(
            'OPTIONS',
            '/users',
            server: [
                'HTTP_ORIGIN' => 'http://localhost:3000',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'If-Match',
            ],
        );

        $response = $this->client->getResponse();
        $allowedHeaders = $response->headers->get('Access-Control-Allow-Headers');

        $this->assertNotNull($allowedHeaders);
        $this->assertStringContainsStringIgnoringCase('If-Match', $allowedHeaders);
    }

    #[Test]
    #[Group('e2e')]
    #[Group('security')]
    public function preflight_rejects_unknown_origin(): void
    {
        $this->client->request(
            'OPTIONS',
            '/users',
            server: [
                'HTTP_ORIGIN' => 'https://evil.example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            ],
        );

        $response = $this->client->getResponse();
        $origin = $response->headers->get('Access-Control-Allow-Origin');

        // Unknown origin should not be in the allowed list
        $this->assertNull($origin);
    }
}
