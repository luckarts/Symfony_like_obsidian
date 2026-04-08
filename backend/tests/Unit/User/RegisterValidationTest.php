<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Validation;

use App\User\Infrastructure\ApiPlatform\Resource\RegisterUserRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Group('unit')]
#[Group('user')]
class RegisterValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get('validator');
    }

    #[Test]
    public function email_blank_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(email: ''));

        $this->assertViolationOn($violations, 'email');
    }

    #[Test]
    public function email_invalid_format_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(email: 'not-an-email'));

        $this->assertViolationOn($violations, 'email');
    }

    #[Test]
    public function password_blank_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(password: ''));

        $this->assertViolationOn($violations, 'password');
    }

    #[Test]
    public function password_too_short_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(password: 'short'));

        $this->assertViolationOn($violations, 'password');
    }

    #[Test]
    public function password_too_weak_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(password: 'password123'));

        $this->assertViolationOn($violations, 'password');
    }

    #[Test]
    public function firstname_blank_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(firstName: ''));

        $this->assertViolationOn($violations, 'firstName');
    }

    #[Test]
    public function lastname_blank_fails(): void
    {
        $violations = $this->validator->validate($this->validDto(lastName: ''));

        $this->assertViolationOn($violations, 'lastName');
    }

    #[Test]
    public function valid_request_passes(): void
    {
        $violations = $this->validator->validate($this->validDto());

        $this->assertCount(0, $violations);
    }

    private function validDto(
        string $email = 'valid@example.com',
        string $password = 'T3st!P@ss#Api42',
        string $firstName = 'John',
        string $lastName = 'Doe',
    ): RegisterUserRequest {
        $dto = new RegisterUserRequest();
        $dto->email = $email;
        $dto->password = $password;
        $dto->firstName = $firstName;
        $dto->lastName = $lastName;

        return $dto;
    }

    private function assertViolationOn(ConstraintViolationListInterface $violations, string $property): void
    {
        $paths = array_map(
            static fn ($v) => $v->getPropertyPath(),
            iterator_to_array($violations),
        );

        $this->assertContains($property, $paths, sprintf(
            'Expected violation on "%s", got violations on: [%s]',
            $property,
            implode(', ', $paths) ?: 'none',
        ));
    }
}