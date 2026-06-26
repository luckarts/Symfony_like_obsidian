<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Service;

use App\User\Domain\Service\PasswordPolicyService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('user')]
class PasswordPolicyServiceTest extends TestCase
{
    private PasswordPolicyService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordPolicyService();
    }

    #[Test]
    public function valid_password_meets_all_requirements(): void
    {
        $violations = $this->service->assertPassword('SecureP@ss123', 'user@example.com');

        $this->assertEmpty($violations);
    }

    #[Test]
    public function password_too_short(): void
    {
        $violations = $this->service->assertPassword('Short1!', 'user@example.com');

        $this->assertContains('Password must be at least 12 characters long.', $violations);
    }

    #[Test]
    public function password_missing_uppercase(): void
    {
        $violations = $this->service->assertPassword('securepass1!', 'user@example.com');

        $this->assertContains('Password must contain at least one uppercase letter.', $violations);
    }

    #[Test]
    public function password_missing_lowercase(): void
    {
        $violations = $this->service->assertPassword('SECUREPASS1!', 'user@example.com');

        $this->assertContains('Password must contain at least one lowercase letter.', $violations);
    }

    #[Test]
    public function password_missing_digit(): void
    {
        $violations = $this->service->assertPassword('SecurePass!abc', 'user@example.com');

        $this->assertContains('Password must contain at least one digit.', $violations);
    }

    #[Test]
    public function password_missing_special_character(): void
    {
        $violations = $this->service->assertPassword('SecurePass123abc', 'user@example.com');

        $this->assertContains('Password must contain at least one special character.', $violations);
    }

    #[Test]
    public function password_equals_email(): void
    {
        $violations = $this->service->assertPassword('user@example.com', 'user@example.com');

        $this->assertContains('Password cannot be the same as your email address.', $violations);
    }

    #[Test]
    public function multiple_violations(): void
    {
        $violations = $this->service->assertPassword('short', 'user@example.com');

        $this->assertCount(4, $violations);
        $this->assertContains('Password must be at least 12 characters long.', $violations);
        $this->assertContains('Password must contain at least one uppercase letter.', $violations);
        $this->assertContains('Password must contain at least one digit.', $violations);
        $this->assertContains('Password must contain at least one special character.', $violations);
    }

    #[Test]
    public function custom_rules_override_defaults(): void
    {
        $rules = [
            'minLength' => 8,
            'requireUppercase' => false,
            'requireLowercase' => true,
            'requireDigit' => false,
            'requireSpecialChar' => false,
            'notEqualToEmail' => true,
        ];

        $service = new PasswordPolicyService($rules);
        $violations = $service->assertPassword('password123', 'user@example.com');

        $this->assertEmpty($violations);
    }

    #[Test]
    public function custom_minlength_enforced(): void
    {
        $rules = ['minLength' => 20];
        $service = new PasswordPolicyService($rules);

        $violations = $service->assertPassword('SecureP@ss123', 'user@example.com');

        $this->assertContains('Password must be at least 20 characters long.', $violations);
    }
}
