<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

class PasswordPolicyService
{
    /** @var array<string, mixed> */
    private array $rules;

    /** @param array<string, mixed> $rules */
    public function __construct(array $rules = [])
    {
        $this->rules = array_merge([
            'minLength' => 12,
            'requireUppercase' => true,
            'requireLowercase' => true,
            'requireDigit' => true,
            'requireSpecialChar' => true,
            'notEqualToEmail' => true,
        ], $rules);
    }

    /** @return array<int, string> */
    public function assertPassword(string $password, string $email): array
    {
        $violations = [];

        if ($this->rules['minLength'] > 0 && strlen($password) < $this->rules['minLength']) {
            $violations[] = sprintf('Password must be at least %d characters long.', $this->rules['minLength']);
        }

        if ($this->rules['requireUppercase'] && !preg_match('/[A-Z]/', $password)) {
            $violations[] = 'Password must contain at least one uppercase letter.';
        }

        if ($this->rules['requireLowercase'] && !preg_match('/[a-z]/', $password)) {
            $violations[] = 'Password must contain at least one lowercase letter.';
        }

        if ($this->rules['requireDigit'] && !preg_match('/[0-9]/', $password)) {
            $violations[] = 'Password must contain at least one digit.';
        }

        if ($this->rules['requireSpecialChar'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $violations[] = 'Password must contain at least one special character.';
        }

        if ($this->rules['notEqualToEmail'] && strtolower($password) === strtolower($email)) {
            $violations[] = 'Password cannot be the same as your email address.';
        }

        return $violations;
    }
}
