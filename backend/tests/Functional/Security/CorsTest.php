<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
#[Group('security')]
class CorsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[Test]
    public function preflight_returns_access_control_allow_origin_for_dev(): void
    {
        $this->client->request(
            'OPTIONS',
            '/api/v1/security/events',
            [],
            [],
            [
                'HTTP_Origin' => 'https://app.obsidian-web.local',
                'HTTP_Access-Control-Request-Method' => 'GET',
            ],
        );

        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertSame('https://app.obsidian-web.local', $response->headers->get('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function preflight_returns_access_control_allow_origin_for_localhost(): void
    {
        $this->client->request(
            'OPTIONS',
            '/api/v1/security/events',
            [],
            [],
            [
                'HTTP_Origin' => 'http://localhost:3000',
                'HTTP_Access-Control-Request-Method' => 'GET',
            ],
        );

        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertSame('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function preflight_allow_headers_contains_if_match(): void
    {
        $this->client->request(
            'OPTIONS',
            '/api/v1/security/events',
            [],
            [],
            [
                'HTTP_Origin' => 'https://app.obsidian-web.local',
                'HTTP_Access-Control-Request-Method' => 'GET',
            ],
        );

        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->has('Access-Control-Allow-Headers'));
        $allowHeaders = $response->headers->get('Access-Control-Allow-Headers');
        $this->assertStringContainsString('if-match', $allowHeaders);
    }

    #[Test]
    public function unknown_origin_is_rejected(): void
    {
        $this->client->request(
            'OPTIONS',
            '/api/v1/security/events',
            [],
            [],
            [
                'HTTP_Origin' => 'https://evil.example.com',
                'HTTP_Access-Control-Request-Method' => 'GET',
            ],
        );

        $response = $this->client->getResponse();

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }
}
