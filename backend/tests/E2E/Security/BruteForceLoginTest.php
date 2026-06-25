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
class BruteForceLoginTest extends WebTestCase
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
        $oauthClient = new Client('Test Client', self::CLIENT_ID, self::CLIENT_SECRET);
        $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
        $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
        $clientManager->save($oauthClient);

        /** @var DoctrineSecurityEventRepository $repository */
        $repository = static::getContainer()->get(DoctrineSecurityEventRepository::class);
        $this->securityEventRepository = $repository;
    }

    #[Test]
    public function too_many_bad_passwords_same_ip_returns_429(): void
    {
        $email = 'brute_force_ip_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);

        // Fix IP for all requests in this test
        $this->client->setServerParameter('REMOTE_ADDR', '10.0.0.99');

        // login_ip limit in test env is 3 per 10s
        $limit = 3;

        // First $limit attempts: wrong password, should be 400 (not throttled yet)
        for ($i = 0; $i < $limit; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => 'wrong_password_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode(), 'Attempt ' . ($i + 1) . ' should be 400');
        }

        // Next attempt ($limit + 1) from same IP: should be 429
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => 'wrong_password_final',
            'scope' => 'email',
        ]);
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode(), 'Expected 429 after IP limit exceeded');

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertSame('slow_down', $content['error'], 'Error type should be slow_down');
        $this->assertArrayHasKey('error_description', $content);
        $this->assertArrayHasKey('hint', $content);
        $this->assertStringContainsString('Please wait before retrying.', $content['hint']);
    }

    #[Test]
    public function blocked_login_writes_login_blocked_event(): void
    {
        $email = 'brute_force_event_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);

        // Fix IP for all requests in this test
        $this->client->setServerParameter('REMOTE_ADDR', '10.0.0.99');

        // Trigger rate limit: login_ip limit is 3, so 4 attempts
        $limit = 3;
        for ($i = 0; $i <= $limit; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => 'wrong_password_' . $i,
                'scope' => 'email',
            ]);
        }

        // Last response should be 429
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->client->getResponse()->getStatusCode());

        // Check that a SECURITY_EVENT_TYPE_LOGIN_BLOCKED row exists
        $row = $this->securityEventRepository->findOneBy(['emailAttempted' => $email], ['id' => 'DESC']);
        $this->assertInstanceOf(SecurityEvent::class, $row);
        $this->assertSame(SecurityEventType::LOGIN_BLOCKED, $row->getEventType());
        $this->assertSame('rate_limited_ip', $row->getReason());
    }

    #[Test]
    public function successful_login_resets_identifier_counter(): void
    {
        $email = 'brute_force_reset_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);

        // IP 1: 10.0.0.1
        $ip1 = '10.0.0.1';
        $this->client->setServerParameter('REMOTE_ADDR', $ip1);

        // 2 bad attempts from IP1 (below both limits)
        for ($i = 0; $i < 2; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => 'wrong_password_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        }

        // Successful login from same IP1
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $token = json_decode($this->client->getResponse()->getContent(), true)['access_token'];

        // Immediately (within same 10s window) 3 more bad attempts from IP 10.0.0.2
        $ip2 = '10.0.0.2';
        $this->client->setServerParameter('REMOTE_ADDR', $ip2);
        for ($i = 0; $i < 3; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $email,
                'password' => 'wrong_password_after_success_reset_wrong_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode(), 'IP 10.0.0.2 attempt ' . ($i + 1) . ' should be 400 (identifier reset)');
        }

        // 1 more bad from IP 10.0.0.2 -> now 3 from this IP -> 429
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => 'wrong_password_final',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function different_ip_independent_throttling(): void
    {
        $emailA = 'brute_force_ip_independent_a_' . uniqid() . '@example.com';
        $emailB = 'brute_force_ip_independent_b_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($emailA, $password);
        $this->registerUser($emailB, $password);

        // IP 1: 10.0.0.1
        $this->client->setServerParameter('REMOTE_ADDR', '10.0.0.1');
        $limit = 3; // login_ip limit in test

        // 3 bad attempts from IP 10.0.0.1 -> 4th -> 429
        for ($i = 0; $i < $limit; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $emailA,
                'password' => 'wrong_ip1_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        }
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $emailA,
            'password' => 'wrong_ip1_final',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->client->getResponse()->getStatusCode());

        // IP 2: 10.0.0.2 (should be independent)
        $this->client->setServerParameter('REMOTE_ADDR', '10.0.0.2');
        // 3 bad attempts from IP 10.0.0.2 with different email -> NOT 429 (first window)
        for ($i = 0; $i < $limit; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $emailB,
                'password' => 'wrong_ip2_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        }
        // 4th from IP 10.0.0.2 -> 429
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $emailB,
            'password' => 'wrong_ip2_final',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function identifier_success_does_not_reset_ip_counter(): void
    {
        $emailA = 'brute_force_ip_persist_a_' . uniqid() . '@example.com';
        $emailB = 'brute_force_ip_persist_b_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($emailA, $password);
        $this->registerUser($emailB, $password);

        $ip = '10.0.0.1';
        $this->client->setServerParameter('REMOTE_ADDR', $ip);

        // 2 bad attempts for user A (below both limits)
        for ($i = 0; $i < 2; $i++) {
            $this->client->request('POST', '/oauth2/token', [
                'grant_type' => 'password',
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'username' => $emailA,
                'password' => 'wrong_a_' . $i,
                'scope' => 'email',
            ]);
            $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        }

        // Successful login for user A (resets identifier counter for A, IP still at 2)
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $emailA,
            'password' => $password,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // 1 bad attempt for user B from same IP
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $emailB,
            'password' => 'wrong_b_first',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        // 2nd bad attempt for user B from same IP -> IP now at 3 (2 from A + 1 from B) -> 429
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $emailB,
            'password' => 'wrong_b_second',
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->client->getResponse()->getStatusCode());
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