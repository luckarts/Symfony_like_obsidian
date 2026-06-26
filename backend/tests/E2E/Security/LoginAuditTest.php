<?php

declare(strict_types=1);

namespace App\Tests\E2E\Security;

use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Infrastructure\Doctrine\DoctrineSecurityEventRepository;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[Group('e2e')]
#[Group('auth')]
class LoginAuditTest extends WebTestCase
{
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';

    private KernelBrowser $client;
    private DoctrineSecurityEventRepository $securityEventRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        /** @var ClientManagerInterface $clientManager */
        $clientManager = static::getContainer()->get(ClientManagerInterface::class);
        if ($clientManager->find(self::CLIENT_ID) === null) {
            $oauthClient = new Client('Test Client', self::CLIENT_ID, self::CLIENT_SECRET);
            $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
            $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
            $clientManager->save($oauthClient);
        }

        /** @var DoctrineSecurityEventRepository $repository */
        $repository = static::getContainer()->get(DoctrineSecurityEventRepository::class);
        $this->securityEventRepository = $repository;
    }

    #[Test]
    public function successful_login_writes_a_login_success_row(): void
    {
        $email = 'audit_success_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $row = $this->securityEventRepository->findOneBy(['emailAttempted' => $email]);
        $this->assertInstanceOf(SecurityEvent::class, $row);
        $this->assertSame(SecurityEventType::LOGIN_SUCCESS, $row->getEventType());
        $this->assertNull($row->getReason());
        $this->assertNotNull($row->getUserId());
    }

    #[Test]
    public function unknown_user_writes_a_login_failed_row_with_user_not_found_reason(): void
    {
        $email = 'audit_unknown_' . uniqid() . '@example.com';

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => 'whatever',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $row = $this->securityEventRepository->findOneBy(['emailAttempted' => $email]);
        $this->assertInstanceOf(SecurityEvent::class, $row);
        $this->assertSame(SecurityEventType::LOGIN_FAILED, $row->getEventType());
        $this->assertSame('user_not_found', $row->getReason());
        $this->assertNull($row->getUserId());
    }

    #[Test]
    public function wrong_password_writes_a_login_failed_row_with_bad_password_reason(): void
    {
        $email = 'audit_badpw_' . uniqid() . '@example.com';

        $this->registerUser($email, 'T3st!P@ss#Api42');

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => 'wrong-password',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $row = $this->securityEventRepository->findOneBy(['emailAttempted' => $email]);
        $this->assertInstanceOf(SecurityEvent::class, $row);
        $this->assertSame(SecurityEventType::LOGIN_FAILED, $row->getEventType());
        $this->assertSame('bad_password', $row->getReason());
        $this->assertNotNull($row->getUserId());
    }

    private function registerUser(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => $password, 'firstName' => 'Jane', 'lastName' => 'Doe']),
        );
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }
}
