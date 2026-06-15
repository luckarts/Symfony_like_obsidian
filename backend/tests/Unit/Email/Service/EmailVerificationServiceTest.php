<?php

declare(strict_types=1);

namespace App\Tests\Unit\Email\Service;

use App\Email\Application\Service\EmailVerificationService;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class VerifyEmailHelperStub implements VerifyEmailHelperInterface
{
    public bool $validated = false;

    /** @param array<string, mixed> $extraParams */
    public function generateSignature(string $routeName, string $userId, string $userEmail, array $extraParams = []): \SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents
    {
        return new \SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents(
            new \DateTimeImmutable('+1 hour'),
            'https://example.com/verify',
        );
    }

    public function validateEmailConfirmation(string $signedUrl, string $userId, string $userEmail): void
    {
    }

    public function validateEmailConfirmationFromRequest(Request $request, string $userId, string $userEmail): void
    {
        $this->validated = true;
    }
}

#[Group('unit')]
#[Group('email')]
class EmailVerificationServiceTest extends TestCase
{
    private VerifyEmailHelperStub $verifyEmailHelper;
    private UserRepositoryInterface&MockObject $userRepository;
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->verifyEmailHelper = new VerifyEmailHelperStub();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new EmailVerificationService($this->verifyEmailHelper, $this->userRepository);
    }

    #[Test]
    public function verify_marks_user_as_verified(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('verify');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-id-123')
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->service->verify('user-id-123', 'test@example.com', new Request());

        $this->assertTrue($this->verifyEmailHelper->validated);
    }

    #[Test]
    public function verify_throws_when_user_not_found(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        $this->service->verify('non-existent', 'test@example.com', new Request());
    }
}
