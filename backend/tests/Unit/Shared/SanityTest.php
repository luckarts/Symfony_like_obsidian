<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use PHPUnit\Framework\TestCase;

class SanityTest extends TestCase
{
    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual(80300, \PHP_VERSION_ID);
    }
}
