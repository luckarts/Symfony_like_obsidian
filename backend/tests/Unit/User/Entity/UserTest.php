<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegisteredEvent;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('user')]
class UserTest extends TestCase
{
    #[Test]
    public function register_records_user_registered_event(): void
    {
        $user = User::register(
            email: 'john@example.com',
            hashedPassword: 'hashed_password',
            firstName: 'John',
            lastName: 'Doe',
        );

        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegisteredEvent::class, $events[0]);

        $event = $events[0];
        $this->assertSame('john@example.com', $event->email);
        $this->assertSame((string) $user->getId(), $event->userId);
    }

    #[Test]
    public function pull_domain_events_clears_recorded_events(): void
    {
        $user = User::register(
            email: 'jane@example.com',
            hashedPassword: 'hashed_password',
            firstName: 'Jane',
            lastName: 'Smith',
        );

        $user->pullDomainEvents();
        $secondPull = $user->pullDomainEvents();

        $this->assertEmpty($secondPull);
    }
}
