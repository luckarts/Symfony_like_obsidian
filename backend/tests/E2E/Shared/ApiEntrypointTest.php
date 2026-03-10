<?php

declare(strict_types=1);

namespace App\Tests\E2E\Shared;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[\PHPUnit\Framework\Attributes\Group('smoke')]
#[\PHPUnit\Framework\Attributes\Group('shared')]
class ApiEntrypointTest extends WebTestCase
{
    public function testApiEntrypointIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT' => 'application/ld+json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
}
