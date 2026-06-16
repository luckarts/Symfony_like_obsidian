<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Tests\E2E\Trait\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApiTestCase extends WebTestCase
{
    use ApiTestHelper;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        $response = $this->client->getResponse();
        $body = $response->getContent();
        $decoded = json_decode($body, true);
        $pretty = null !== $decoded
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $body;

        fwrite(STDERR, sprintf(
            "\n[DEBUG] Status: %d\n[DEBUG] Response:\n%s\n",
            $response->getStatusCode(),
            $pretty ?: '(empty)',
        ));

        parent::onNotSuccessfulTest($t);
    }
}
