<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Symfony\Validator;

use Symfony\Component\Validator\Constraint;

class PasswordPolicy extends Constraint
{
    public string $message = 'This password does not meet the required security policy.';
    /** @var array<string, mixed> */
    public array $rules = [];
    public ?string $emailField = null;

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed>|null $groups
     */
    public function __construct(
        array $rules = [],
        ?string $emailField = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
        $this->rules = $rules;
        $this->emailField = $emailField;
        if ($message !== null) {
            $this->message = $message;
        }
    }
}
