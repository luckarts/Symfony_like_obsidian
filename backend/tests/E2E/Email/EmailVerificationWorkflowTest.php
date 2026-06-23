<?php

declare(strict_types=1);

namespace App\Tests\E2E\Email;

use App\Tests\E2E\AbstractApiTestCase;
use App\User\Domain\Contract\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

#[Group('e2e')]
#[Group('email')]
class EmailVerificationWorkflowTest extends AbstractApiTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    #[Group('smoke')]
    public function register_sends_verification_email_and_verify_link_marks_user_as_verified(): void
    {
        $email = 'workflow-' . uniqid() . '@example.com';
        $headers = ['CONTENT_TYPE' => 'application/ld+json'];
        $payload = [
            'email' => $email,
            'password' => 'T3st!P@ss#Api42',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $this->client->request('POST', '/api/users', [], [], $headers, json_encode($payload));
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{id: string, isVerified: bool} $data */
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['isVerified']);

        $this->assertEmailCount(1);
        $sentEmail = $this->getMailerMessage(0);
        $this->assertNotNull($sentEmail);
        $this->assertEmailHtmlBodyContains($sentEmail, 'Confirm my email');

        $this->assertInstanceOf(Email::class, $sentEmail);
        $html = (string) $sentEmail->getHtmlBody();
        preg_match('/href="([^"]+)"/', $html, $matches);
        $this->assertArrayHasKey(1, $matches, 'Signed verification URL not found in email body.');
        $signedUrl = html_entity_decode($matches[1]);

        $this->client->request('GET', $signedUrl);
        $verifyResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $verifyResponse->getStatusCode(), (string) $verifyResponse->getContent());

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = $userRepository->findById($data['id']);
        $this->assertNotNull($user);
        $this->assertTrue($user->isVerified());
    }
}
