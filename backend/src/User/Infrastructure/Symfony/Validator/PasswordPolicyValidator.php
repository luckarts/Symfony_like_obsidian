<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Symfony\Validator;

use App\User\Domain\Service\PasswordPolicyService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordPolicyValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PasswordPolicyService $passwordPolicyService,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordPolicy) {
            throw new UnexpectedTypeException($constraint, PasswordPolicy::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $email = '';
        if ($constraint->emailField !== null) {
            $data = $this->context->getRoot();
            $email = $data->{$constraint->emailField} ?? '';
        }

        $violations = $this->passwordPolicyService->assertPassword((string) $value, $email);

        foreach ($violations as $violation) {
            $this->context->addViolation($violation);
        }
    }
}
