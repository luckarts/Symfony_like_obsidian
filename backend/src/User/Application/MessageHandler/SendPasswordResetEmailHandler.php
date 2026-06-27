<?php

declare(strict_types=1);

namespace App\User\Application\MessageHandler;

use App\User\Application\Message\SendPasswordResetEmailMessage;
use App\User\Application\Service\PasswordResetService;
use App\User\Domain\Contract\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
class SendPasswordResetEmailHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl,
    ) {
    }

    public function __invoke(SendPasswordResetEmailMessage $message): void
    {
        $user = $this->userRepository->findById($message->userId);

        if (null === $user) {
            return;
        }

        $resetUrl = sprintf(
            '%s/auth/reset-password?token=%s',
            rtrim($this->frontendUrl, '/'),
            $message->resetToken,
        );

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail(), $user->getFirstName().' '.$user->getLastName()))
            ->subject('Reset your password')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'expiresInHours' => PasswordResetService::TOKEN_EXPIRATION_HOURS,
            ]);

        $this->mailer->send($email);
    }
}
