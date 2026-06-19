<?php

declare(strict_types=1);

namespace App\Email\Application\MessageHandler;

use App\Email\Application\Message\SendVerificationEmailMessage;
use App\User\Domain\Contract\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[AsMessageHandler]
class SendVerificationEmailHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendVerificationEmailMessage $message): void
    {
        $user = $this->userRepository->findById($message->userId);

        if (null === $user) {
            return;
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'api_email_verify',
            (string) $user->getId(),
            $user->getEmail(),
            [
                'userId' => (string) $user->getId(),
                'userEmail' => $user->getEmail(),
            ],
        );

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail(), $user->getFirstName().' '.$user->getLastName()))
            ->subject('Please confirm your email address')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAt' => $signatureComponents->getExpiresAt(),
            ]);

        $this->mailer->send($email);
    }
}
