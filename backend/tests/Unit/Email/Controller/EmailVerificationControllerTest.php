<?php

declare(strict_types=1);

namespace App\Tests\Unit\Email\Controller;

use App\Email\Application\Service\EmailVerificationService;
use App\Email\Infrastructure\Controller\EmailVerificationController;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

#[Group('unit')]
#[Group('email')]
class EmailVerificationControllerTest extends TestCase
{
    private EmailVerificationService&MockObject $emailVerificationService;
    private EmailVerificationController $controller;

    protected function setUp(): void
    {
        $this->emailVerificationService = $this->createMock(EmailVerificationService::class);
        $this->controller = new EmailVerificationController($this->emailVerificationService);
    }

    #[Test]
    public function verify_returns_not_found_on_user_not_found(): void
    {
        $request = new Request(['userId' => 'missing-user', 'userEmail' => 'test@example.com']);

        $this->emailVerificationService
            ->expects($this->once())
            ->method('verify')
            ->willThrowException(UserNotFoundException::withId('missing-user'));

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertStringContainsString('missing-user', $data['error']);
    }

    #[Test]
    public function verify_returns_bad_request_on_expired_signature(): void
    {
        $request = new Request(['userId' => 'user-id', 'userEmail' => 'test@example.com']);

        $exception = new ExpiredSignatureException();
        $this->emailVerificationService
            ->expects($this->once())
            ->method('verify')
            ->willThrowException($exception);

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString($exception->getReason(), (string) $response->getContent());
    }

    #[Test]
    public function verify_returns_bad_request_on_invalid_signature(): void
    {
        $request = new Request(['userId' => 'user-id', 'userEmail' => 'test@example.com']);

        $exception = new InvalidSignatureException();
        $this->emailVerificationService
            ->expects($this->once())
            ->method('verify')
            ->willThrowException($exception);

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString($exception->getReason(), (string) $response->getContent());
    }

    #[Test]
    public function verify_returns_bad_request_on_wrong_email(): void
    {
        $request = new Request(['userId' => 'user-id', 'userEmail' => 'test@example.com']);

        $exception = new WrongEmailVerifyException();
        $this->emailVerificationService
            ->expects($this->once())
            ->method('verify')
            ->willThrowException($exception);

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString($exception->getReason(), (string) $response->getContent());
    }

    #[Test]
    public function verify_returns_ok_on_success(): void
    {
        $request = new Request(['userId' => 'user-id', 'userEmail' => 'test@example.com']);

        $this->emailVerificationService
            ->expects($this->once())
            ->method('verify');

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Email verified successfully.', (string) $response->getContent());
    }

    #[Test]
    public function verify_returns_bad_request_on_missing_parameters(): void
    {
        $request = new Request([]);

        $this->emailVerificationService->expects($this->never())->method('verify');

        $response = $this->controller->verify($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Missing verification parameters.', (string) $response->getContent());
    }
}
