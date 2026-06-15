<?php

declare(strict_types=1);

namespace App\Tests\Unit\Email\MessageHandler;

use App\Email\Application\Message\SendVerificationEmailMessage;
use App\Email\Application\MessageHandler\SendVerificationEmailHandler;
use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

#[Group('unit')]
#[Group('email')]
class SendVerificationEmailHandlerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private VerifyEmailHelperInterface&MockObject $verifyEmailHelper;
    private MailerInterface&MockObject $mailer;
    private Environment&MockObject $twig;
    private SendVerificationEmailHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->handler = new SendVerificationEmailHandler(
            $this->userRepository,
            $this->verifyEmailHelper,
            $this->mailer,
            $this->twig,
        );
    }

    #[Test]
    public function sends_verification_email(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-123');
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with('user-123')
            ->willReturn($user);

        $signatureComponents = new VerifyEmailSignatureComponents(
            new \DateTimeImmutable('+1 hour'),
            'https://example.com/verify?token=abc',
            1234567890,
        );

        $this->verifyEmailHelper
            ->expects($this->once())
            ->method('generateSignature')
            ->with('api_email_verify', 'user-123', 'test@example.com')
            ->willReturn($signatureComponents);

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with('emails/verify_email.html.twig', $this->arrayHasKey('signedUrl'))
            ->willReturn('<html>email content</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->handler->__invoke(new SendVerificationEmailMessage('user-123'));
    }

    #[Test]
    public function skips_when_user_not_found(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->verifyEmailHelper->expects($this->never())->method('generateSignature');
        $this->mailer->expects($this->never())->method('send');

        $this->handler->__invoke(new SendVerificationEmailMessage('non-existent'));
    }
}
